<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;

interface PayPalTransportInterface
{
    /**
     * @param PaymentInfo     $paymentInfo
     * @param CredentialsInfo $credentialsInfo
     * @param string          $successRoute Route where PayPal will redirect user after payment approve
     * @param string          $failedRoute Route where PayPal will redirect user after payment cancel
     *
     * @return string Link where user should approve payment
     * @throws ExceptionInterface
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
     * @throws ExceptionInterface
     */
    public function executePayment(PaymentInfo $paymentInfo, CredentialsInfo $credentialsInfo);
}
