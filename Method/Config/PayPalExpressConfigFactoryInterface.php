<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;

interface PayPalExpressConfigFactoryInterface
{
    /**
     * @param PayPalExpressSettings $settings
     *
     * @return PayPalExpressConfigInterface
     */
    public function createConfig(PayPalExpressSettings $settings);
}
