<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedCurrencyException;
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
     * @param PaymentTransaction $paymentTransaction
     *
     * @return PaymentInfo
     */
    public function getPaymentInfo(PaymentTransaction $paymentTransaction)
    {
        $this->validateTransaction($paymentTransaction);

        $paymentEntity = $this->getPaymentEntity($paymentTransaction);
        $currency = $paymentTransaction->getCurrency();

        $paymentItems = [];
        if ($paymentEntity instanceof LineItemsAwareInterface) {
            foreach ($paymentEntity->getLineItems() as $lineItem) {
                if ($this->paymentItemTranslator->tryGetPaymentItemInfo($lineItem, $currency, $item)) {
                    $paymentItems[] = $item;
                }
            }
        }

        $paymentInfo = new PaymentInfo(
            $paymentTransaction->getAmount(),
            $currency,
            $this->getShipping($paymentEntity),
            $this->taxProvider->getTax($paymentEntity),
            $this->getSubtotal($paymentEntity),
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            $paymentItems
        );

        return $paymentInfo;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    protected function validateTransaction(PaymentTransaction $paymentTransaction)
    {
        if (!$this->supportedCurrenciesHelper->isFullySupporterdCurrency($paymentTransaction->getCurrency())) {
            $exception = UnsupportedCurrencyException::create(
                $paymentTransaction->getCurrency(),
                $this->supportedCurrenciesHelper->getFullySupportedCurrencyCodes()
            );

            throw $exception;
        }
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
