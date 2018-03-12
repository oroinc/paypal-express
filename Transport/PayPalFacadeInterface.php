<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;

interface PayPalFacadeInterface
{
    /**
     * @param PaymentTransaction $paymentTransaction
     * @param string             $clentId
     * @param string             $clientSecret
     * @param string             $successRoute
     * @param string             $failedRoute
     *
     * @return string
     */
    public function getPayPalPaymentRoute(
        PaymentTransaction $paymentTransaction,
        $clentId,
        $clientSecret,
        $successRoute,
        $failedRoute
    );

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param string             $clentId
     * @param string             $clientSecret
     * @param string             $paymentId
     * @param string             $payerId
     * @throws \PayPal\Exception\PayPalConnectionException
     * @throws \Throwable
     */
    public function executePayPalPayment(
        PaymentTransaction $paymentTransaction,
        $clentId,
        $clientSecret,
        $paymentId,
        $payerId
    );
}
