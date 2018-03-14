<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;

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
     * @param IntegrationIdentifierGeneratorInterface $identifierGenerator
     * @param LocalizationHelper                      $localizationHelper
     */
    public function __construct(
        IntegrationIdentifierGeneratorInterface $identifierGenerator,
        LocalizationHelper $localizationHelper
    ) {
        $this->identifierGenerator = $identifierGenerator;
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function createConfig(PayPalExpressSettings $settings)
    {
        return new PayPalExpressConfig(
            $this->getLocalizedValue($settings->getLabels()),
            $this->getLocalizedValue($settings->getShortLabels()),
            $settings->getName(),
            $settings->getClientId(),
            $settings->getClientSecret(),
            $this->identifierGenerator->generateIdentifier($settings->getChannel()),
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
}
