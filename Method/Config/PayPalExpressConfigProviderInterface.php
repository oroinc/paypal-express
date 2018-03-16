<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

interface PayPalExpressConfigProviderInterface
{
    /**
     * @return PayPalExpressConfigInterface[]
     */
    public function getPaymentConfigs();
}
