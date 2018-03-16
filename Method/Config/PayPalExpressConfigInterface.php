<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;

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
