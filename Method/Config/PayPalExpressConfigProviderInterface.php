<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

/**
 * Provides instances of {@see PayPalExpressConfigInterface} for {@see PayPalExpressMethodProvider}.
 */
interface PayPalExpressConfigProviderInterface
{
    /**
     * @return PayPalExpressConfigInterface[]
     */
    public function getPaymentConfigs();
}
