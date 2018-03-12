<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedCurrencyException;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedValueException;
use Oro\Bundle\PayPalExpressBundle\Provider\TaxProvider;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

class PaymentInfoTranslator
{
    /**
     * @var SupportedCurrenciesHelper
     */
    protected $supportedCurrenciesHelper;

    /**
     * @var PaymentItemTranslator
     */
    protected $paymentItemTranslator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var TaxProvider
     */
    protected $taxProvider;

    /**
     * @param SupportedCurrenciesHelper $supportedCurrenciesHelper
     * @param PaymentItemTranslator     $paymentItemTranslator
     * @param DoctrineHelper            $doctrineHelper
     * @param TaxProvider               $taxProvider
     */
    public function __construct(
        SupportedCurrenciesHelper $supportedCurrenciesHelper,
        PaymentItemTranslator $paymentItemTranslator,
        DoctrineHelper $doctrineHelper,
        TaxProvider $taxProvider
    ) {
        $this->supportedCurrenciesHelper = $supportedCurrenciesHelper;
        $this->paymentItemTranslator     = $paymentItemTranslator;
        $this->doctrineHelper            = $doctrineHelper;
        $this->taxProvider               = $taxProvider;
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
        $paymentItems = $this->getPaymentItems($paymentEntity, $currency);

        $paymentInfo = new PaymentInfo($amount, $currency, $shipping, $tax, $subtotal, $method, $paymentItems);

        return $paymentInfo;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    protected function validateTransaction(PaymentTransaction $paymentTransaction)
    {
        $currency = $paymentTransaction->getCurrency();

        if (!$this->supportedCurrenciesHelper->isSupportedCurrency($currency)) {
            $supportedCurrencyCodes = $this->supportedCurrenciesHelper->getSupportedCurrencyCodes();
            $exception = UnsupportedCurrencyException::create($currency, $supportedCurrencyCodes);

            throw $exception;
        }
        if ($this->supportedCurrenciesHelper->isCurrencyWithUnsupportedDecimals($currency)) {
            $amount = (float)$paymentTransaction->getAmount();
            if ($amount > floor($amount)) {
                throw new UnsupportedValueException(
                    sprintf(
                        'Decimal amount "%s" is not supported for currency "%s"',
                        $paymentTransaction->getAmount(),
                        $currency
                    )
                );
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
                $itemInfo = $this->paymentItemTranslator->getPaymentItemInfo($lineItem, $currency);
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
}
