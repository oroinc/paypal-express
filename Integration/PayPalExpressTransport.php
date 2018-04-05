<?php

namespace Oro\Bundle\PayPalExpressBundle\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Form\Type\PayPalExpressSettingsType;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Transport implementation of PayPal Express payment method.
 * Required by {@see \Oro\Bundle\IntegrationBundle\OroIntegrationBundle}.
 */
class PayPalExpressTransport implements TransportInterface
{
    /** @var ParameterBag */
    protected $settings;

    /**
     * @param Transport $transportEntity
     */
    public function init(Transport $transportEntity)
    {
        $this->settings = $transportEntity->getSettingsBag();
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return PayPalExpressSettingsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return PayPalExpressSettings::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.paypal_express.settings.label';
    }
}
