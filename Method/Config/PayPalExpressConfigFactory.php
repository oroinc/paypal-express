<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

/**
 * Implementation of interface for constructing {@see PayPalExpressConfig} class
 * based on {@see PayPalExpressSettings} entity. It is used by {@see PayPalExpressConfigProvider}.
 */
class PayPalExpressConfigFactory implements PayPalExpressConfigFactoryInterface
{
    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    protected $identifierGenerator;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var SymmetricCrypterInterface
     */
    protected $encoder;

    public function __construct(
        IntegrationIdentifierGeneratorInterface $identifierGenerator,
        LocalizationHelper $localizationHelper,
        SymmetricCrypterInterface $encoder
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->localizationHelper  = $localizationHelper;
        $this->encoder             = $encoder;
    }

    #[\Override]
    public function createConfig(PayPalExpressSettings $settings)
    {
        return new PayPalExpressConfig(
            $this->getLocalizedValue($settings->getLabels()),
            $this->getLocalizedValue($settings->getShortLabels()),
            $settings->getChannel()->getName(),
            $this->getDecryptedValue($settings->getClientId()),
            $this->getDecryptedValue($settings->getClientSecret()),
            $this->identifierGenerator->generateIdentifier($settings->getChannel()),
            $settings->getPaymentAction(),
            $settings->isSandboxMode()
        );
    }

    /**
     * @param Collection $values
     * @return string
     */
    protected function getLocalizedValue(Collection $values)
    {
        return (string)$this->localizationHelper->getLocalizedValue($values);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function getDecryptedValue($value)
    {
        return (string)$this->encoder->decryptData($value);
    }
}
