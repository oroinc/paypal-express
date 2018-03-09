<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface PayPalFacadeInterface
{
    /**
     * @param PaymentTransaction $paymentTransaction
     * @param                    $clentId
     * @param                    $clientSecret
     * @param                    $successRoute
     * @param                    $failedRoute
     * @return mixed
     */
    public function getPayPalPaymentRoute(
        PaymentTransaction $paymentTransaction,
        $clentId,
        $clientSecret,
        $successRoute,
        $failedRoute
    );
}
