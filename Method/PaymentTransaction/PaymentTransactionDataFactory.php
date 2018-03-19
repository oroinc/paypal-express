<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;

class PaymentTransactionDataFactory
{
    /**
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $config
     * @param PaymentInfo                  $paymentInfo
     *
     * @return PaymentTransactionResponseData
     */
    public function createResponseData(
        PaymentTransaction $paymentTransaction,
        PayPalExpressConfigInterface $config,
        PaymentInfo $paymentInfo = null
    ) {
        $paymentTransactionData = new PaymentTransactionResponseData();
        $paymentTransactionData->setPaymentAction($paymentTransaction->getAction());
        $paymentTransactionData->setPaymentActionConfig($config->getPaymentAction());

        if ($paymentInfo) {
            $paymentTransactionData->setOrderId($paymentInfo->getOrderId());
            $paymentTransactionData->setPaymentId($paymentInfo->getPaymentId());
        } else {
            $paymentTransactionData->setPaymentId($paymentTransaction->getReference());
        }

        return $paymentTransactionData;
    }

    /**
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $config
     *
     * @return PaymentTransactionRequestData
     */
    public function createRequestData(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $paymentTransactionData = new PaymentTransactionRequestData();
        $paymentTransactionData->setPaymentAction($paymentTransaction->getAction());
        $paymentTransactionData->setPaymentActionConfig($config->getPaymentAction());
        $paymentTransactionData->setPaymentId($paymentTransaction->getReference());
        $paymentTransactionData->setCurrency($paymentTransaction->getCurrency());
        $paymentTransactionData->setTotalAmount($paymentTransaction->getAmount());

        return $paymentTransactionData;
    }
}
