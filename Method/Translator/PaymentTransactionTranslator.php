<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Translator;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedCurrencyException;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedValueException;
use Oro\Bundle\PayPalExpressBundle\Provider\TaxProvider;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PaymentTransactionTranslator
{
    /**
     * @var SupportedCurrenciesHelper
     */
    protected $supportedCurrenciesHelper;

    /**
     * @var LineItemTranslator
     */
    protected $lineItemTranslator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var TaxProvider
     */
    protected $taxProvider;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var ExceptionFactory
     */
    protected $exceptionFactory;

    /**
     * @param SupportedCurrenciesHelper $supportedCurrenciesHelper
     * @param LineItemTranslator        $lineItemTranslator
     * @param DoctrineHelper            $doctrineHelper
     * @param TaxProvider               $taxProvider
     * @param RouterInterface           $router
     * @param ExceptionFactory          $exceptionFactory
     */
    public function __construct(
        SupportedCurrenciesHelper $supportedCurrenciesHelper,
        LineItemTranslator $lineItemTranslator,
        DoctrineHelper $doctrineHelper,
        TaxProvider $taxProvider,
        RouterInterface $router,
        ExceptionFactory $exceptionFactory
    ) {
        $this->supportedCurrenciesHelper = $supportedCurrenciesHelper;
        $this->lineItemTranslator        = $lineItemTranslator;
        $this->doctrineHelper            = $doctrineHelper;
        $this->taxProvider               = $taxProvider;
        $this->router                    = $router;
        $this->exceptionFactory          = $exceptionFactory;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return PaymentInfo
     */
    public function getPaymentInfo(PaymentTransaction $paymentTransaction)
    {
        $this->validateTransaction($paymentTransaction);

        $paymentEntity = $this->getPaymentEntity($paymentTransaction);

        $amount = $paymentTransaction->getAmount();
        $currency = $paymentTransaction->getCurrency();
        $shipping = $this->getShipping($paymentEntity);
        $tax = $this->taxProvider->getTax($paymentEntity);
        $subtotal = $this->getSubtotal($paymentEntity);
        $method = PaymentInfo::PAYMENT_METHOD_PAYPAL;
        $invoiceNumber = $this->getInvoiceNumber($paymentEntity);
        $paymentItems = $this->getPaymentItems($paymentEntity, $currency);

        $paymentInfo = new PaymentInfo(
            $amount,
            $currency,
            $shipping,
            $tax,
            $subtotal,
            $method,
            $invoiceNumber,
            $paymentItems
        );

        return $paymentInfo;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @throws UnsupportedCurrencyException
     * @throws UnsupportedValueException
     */
    protected function validateTransaction(PaymentTransaction $paymentTransaction)
    {
        $currency = $paymentTransaction->getCurrency();

        if (!$this->supportedCurrenciesHelper->isSupportedCurrency($currency)) {
            $exception = $this->exceptionFactory->createUnsupportedCurrencyException($currency);

            throw $exception;
        }
        if ($this->supportedCurrenciesHelper->isCurrencyWithUnsupportedDecimals($currency)) {
            $amount = (float)$paymentTransaction->getAmount();
            if ($amount > floor($amount)) {
                $exception = $this->exceptionFactory
                    ->createUnsupportedValueException(
                        sprintf(
                            'Decimal amount "%s" is not supported for currency "%s"',
                            $paymentTransaction->getAmount(),
                            $currency
                        )
                    );

                throw $exception;
            }
        }
    }

    /**
     * @param object $paymentEntity
     * @param string $currency
     *
     * @return array
     */
    protected function getPaymentItems($paymentEntity, $currency)
    {
        $paymentItems = [];
        if ($paymentEntity instanceof LineItemsAwareInterface) {
            foreach ($paymentEntity->getLineItems() as $lineItem) {
                $itemInfo = $this->lineItemTranslator->getPaymentItemInfo($lineItem, $currency);
                if ($itemInfo) {
                    $paymentItems[] = $itemInfo;
                }
            }
        }

        return $paymentItems;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return object
     */
    protected function getPaymentEntity(PaymentTransaction $paymentTransaction)
    {
        return $this->doctrineHelper
            ->getEntity($paymentTransaction->getEntityClass(), $paymentTransaction->getEntityIdentifier());
    }

    /**
     * @param object $paymentEntity
     *
     * @return int|float
     */
    protected function getShipping($paymentEntity)
    {
        if ($paymentEntity instanceof ShippingAwareInterface) {
            $cost = $paymentEntity->getShippingCost();
            if ($cost instanceof Price) {
                return $cost->getValue();
            }

            return (float)$cost;
        }

        return 0;
    }

    /**
     * @param $paymentEntity
     *
     * @return float|int
     */
    protected function getSubtotal($paymentEntity)
    {
        if ($paymentEntity instanceof SubtotalAwareInterface) {
            return $paymentEntity->getSubtotal();
        }

        return 0;
    }

    /**
     * @param $paymentEntity
     *
     * @return string
     */
    protected function getInvoiceNumber($paymentEntity)
    {
        if ($paymentEntity instanceof Order) {
            return $paymentEntity->getIdentifier();
        }

        return uniqid();
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return RedirectRoutesInfo
     */
    public function getRedirectRoutes(PaymentTransaction $paymentTransaction)
    {
        $successRoute = $this->router->generate(
            'oro_payment_callback_return',
            [
                'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $failedRoute = $this->router->generate(
            'oro_payment_callback_error',
            [
                'accessIdentifier' => $paymentTransaction->getAccessIdentifier(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new RedirectRoutesInfo($successRoute, $failedRoute);
    }
}
