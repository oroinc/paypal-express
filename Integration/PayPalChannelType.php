<?php

namespace Oro\Bundle\PayPalExpressBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class PayPalChannelType implements ChannelInterface, IconAwareIntegrationInterface
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
