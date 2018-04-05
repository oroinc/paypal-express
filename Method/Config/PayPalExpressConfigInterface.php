<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

/**
 * Extends {@see PaymentConfigInterface} and adds additional settings specific to PayPal Express payment method.
 */
interface PayPalExpressConfigInterface extends PaymentConfigInterface
{
    /**
     * @return string
     */
    public function getClientId();

    /**
     * @return string
     */
    public function getClientSecret();

    /**
     * @return bool
     */
    public function isSandbox();

    /**
     * @return string
     */
    public function getPaymentAction();
}
