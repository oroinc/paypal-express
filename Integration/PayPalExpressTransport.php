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

    #[\Override]
    public function init(Transport $transportEntity)
    {
        $this->settings = $transportEntity->getSettingsBag();
    }

    #[\Override]
    public function getSettingsFormType()
    {
        return PayPalExpressSettingsType::class;
    }

    #[\Override]
    public function getSettingsEntityFQCN()
    {
        return PayPalExpressSettings::class;
    }

    #[\Override]
    public function getLabel()
    {
        return 'oro.paypal_express.settings.label';
    }
}
