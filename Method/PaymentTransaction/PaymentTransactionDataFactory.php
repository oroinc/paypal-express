<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;

/**
 * Constructs classes for PayPal request and response data:
 * - {@see PaymentTransactionRequestData}
 * - {@see PaymentTransactionResponseData}
 */
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
            $paymentTransactionData->setPayerId($paymentInfo->getPayerId());
        } else {
            $paymentTransactionData->setPaymentId($paymentTransaction->getReference());
        }

        return $paymentTransactionData;
    }

    /**
     * @param array $data
     *
     * @return PaymentTransactionResponseData
     */
    public function createResponseDataFromArray(array $data)
    {
        $paymentTransactionData = new PaymentTransactionResponseData();

        $paymentTransactionData->setFromArray($data);

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

    /**
     * @param array $data
     *
     * @return PaymentTransactionRequestData
     */
    public function createRequestDataFromArray(array $data)
    {
        $paymentTransactionData = new PaymentTransactionRequestData();

        $paymentTransactionData->setFromArray($data);

        return $paymentTransactionData;
    }
}
