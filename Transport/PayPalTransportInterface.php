<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use PayPal\Api\Order;
use PayPal\Api\Payment;
use PayPal\Exception\PayPalConnectionException;

interface PayPalTransportInterface
{
    /**
     * @param PaymentInfo     $paymentInfo
     * @param CredentialsInfo $credentialsInfo
     * @param string          $successRoute Route where PayPal will redirect user after payment approve
     * @param string          $failedRoute Route where PayPal will redirect user after payment cancel
     *
     * @return string Link where user should approve payment
     * @throws PayPalConnectionException
     * @throws \Throwable
     */
    public function setupPayment(
        PaymentInfo $paymentInfo,
        CredentialsInfo $credentialsInfo,
        $successRoute,
        $failedRoute
    );

    /**
     * @param PaymentInfo     $paymentInfo
     * @param CredentialsInfo $credentialsInfo
     * @throws PayPalConnectionException
     * @throws \Throwable
     */
    public function executePayment(PaymentInfo $paymentInfo, CredentialsInfo $credentialsInfo);
}
