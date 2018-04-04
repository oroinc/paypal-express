<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

/**
 * Responsible for provide PayPalExpressConfigProvider public interface
 */
interface PayPalExpressConfigProviderInterface
{
    /**
     * @return PayPalExpressConfigInterface[]
     */
    public function getPaymentConfigs();
}
