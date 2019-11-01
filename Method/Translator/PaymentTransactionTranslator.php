<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Translator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedCurrencyException;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedValueException;
use Oro\Bundle\PayPalExpressBundle\Provider\TaxProvider;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Translates {@see PaymentTransaction} to {@see PaymentInfo}, and {@see PaymentTransaction}
 * to {@see RedirectRoutesInfo}.
 */
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
     * @var SurchargeProvider
     */
    protected $surchargeProvider;

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
     * @param SurchargeProvider         $surchargeProvider
     * @param RouterInterface           $router
     * @param ExceptionFactory          $exceptionFactory
     */
    public function __construct(
        SupportedCurrenciesHelper $supportedCurrenciesHelper,
        LineItemTranslator $lineItemTranslator,
        DoctrineHelper $doctrineHelper,
        TaxProvider $taxProvider,
        SurchargeProvider $surchargeProvider,
        RouterInterface $router,
        ExceptionFactory $exceptionFactory
    ) {
        $this->supportedCurrenciesHelper = $supportedCurrenciesHelper;
        $this->lineItemTranslator        = $lineItemTranslator;
        $this->doctrineHelper            = $doctrineHelper;
        $this->taxProvider               = $taxProvider;
        $this->surchargeProvider         = $surchargeProvider;
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

        $surcharge = $this->surchargeProvider->getSurcharges($paymentEntity);

        $shipping = $surcharge->getShippingAmount();
        $amount = $paymentTransaction->getAmount();
        $tax = $this->taxProvider->getTax($paymentEntity);
        $subtotal = $this->getSubtotal($paymentEntity, $surcharge);
        $method = PaymentInfo::PAYMENT_METHOD_PAYPAL;
        $invoiceNumber = $this->getInvoiceNumber($paymentEntity);
        $currency = $paymentTransaction->getCurrency();
        $paymentItems = $this->getPaymentItems($paymentEntity, $surcharge, $currency);

        $paymentInfo = new PaymentInfo(
            $this->lineItemTranslator->roundForPayPal($amount),
            $currency,
            $this->lineItemTranslator->roundForPayPal($shipping),
            $this->lineItemTranslator->roundForPayPal($tax),
            $this->lineItemTranslator->roundForPayPal($subtotal),
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
     * @param object    $paymentEntity
     * @param Surcharge $surcharge
     * @param string    $currency
     *
     * @return array
     */
    protected function getPaymentItems($paymentEntity, Surcharge $surcharge, $currency)
    {
        $paymentItems = [];
        if ($paymentEntity instanceof LineItemsAwareInterface) {
            $paymentItems = $this->lineItemTranslator->getPaymentItems($paymentEntity, $surcharge, $currency);
        }

        // Replace all line items with totals item, if line items SUM != Subtotal
        // Without Line Items totals are not shown at PayPal which decreases UX and may lead to incomplete purchases.
        $paymentItemsSum = (float)array_sum(
            array_map(
                function (ItemInfo $info) {
                    return $info->getPrice() * $info->getQuantity();
                },
                $paymentItems
            )
        );
        $subtotal = $this->getSubtotal($paymentEntity, $surcharge);
        if ($this->lineItemTranslator->roundForPayPal($subtotal) !== $paymentItemsSum) {
            $paymentItems = [$this->lineItemTranslator->createTotalLineItem($currency, $subtotal)];
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
     * @param object|SubtotalAwareInterface $paymentEntity
     * @param Surcharge                     $surcharge
     *
     * @return float
     */
    protected function getSubtotal($paymentEntity, Surcharge $surcharge)
    {
        if ($paymentEntity instanceof SubtotalAwareInterface) {
            return $this->calculateSubtotal($paymentEntity, $surcharge);
        }

        return 0.0;
    }

    /**
     * Discount amount is not included in subtotal of Oro's payment entity,
     * but it should be included in PayPal's payment DTO.
     *
     * @param SubtotalAwareInterface $paymentEntity
     * @param Surcharge              $surcharge
     * @return float
     */
    protected function calculateSubtotal(SubtotalAwareInterface $paymentEntity, Surcharge $surcharge)
    {
        // Discount amount is a negative number.
        return $paymentEntity->getSubtotal() + $surcharge->getDiscountAmount();
    }

    /**
     * @param object|Order $paymentEntity
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
