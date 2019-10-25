<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;

/**
 * Interface for constructing {@see PayPalExpressConfig} class based on {@see PayPalExpressSettings} entity.
 * It is used by {@see PayPalExpressConfigProvider}.
 */
interface PayPalExpressConfigFactoryInterface
{
    /**
     * @param PayPalExpressSettings $settings
     *
     * @return PayPalExpressConfigInterface
     */
    public function createConfig(PayPalExpressSettings $settings);
}
