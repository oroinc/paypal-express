<?php

namespace Oro\Bundle\PayPalExpressBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Single channel for this payment integration. Required by {@see \Oro\Bundle\IntegrationBundle\OroIntegrationBundle}.
 */
class PayPalExpressChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'paypal_express';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.paypal_express.channel_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/oropaypalexpress/img/paypal-logo.png';
    }
}
