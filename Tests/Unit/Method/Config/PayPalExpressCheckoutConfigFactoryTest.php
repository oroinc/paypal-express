<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfig;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigFactory;

class PayPalExpressCheckoutConfigFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PayPalExpressConfigFactory
     */
    protected $factory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|IntegrationIdentifierGeneratorInterface
     */
    protected $identifierGenerator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LocalizationHelper
     */
    protected $localizationHelper;

    protected function setUp()
    {
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);

        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->factory = new PayPalExpressConfigFactory($this->identifierGenerator, $this->localizationHelper);
    }

    public function testCreateConfig()
    {
        $fooPaymentMethodIdentifier = 'pay_pal_express_1';
        $fooName = 'foo integration';
        $fooClientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $fooClientSecret = 'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $fooLabel = 'foo label';
        $fooShortLabel = 'foo short label';
        $fooIsSandbox = true;
        $fooChannel = new Channel();
        $fooChannel->setName('foo channel');

        $fooSetting = $this->getSetting(
            $fooName,
            $fooClientId,
            $fooClientSecret,
            $fooLabel,
            $fooShortLabel,
            $fooIsSandbox,
            $fooChannel
        );

        $expectedConfig = new PayPalExpressConfig(
            $fooLabel,
            $fooShortLabel,
            $fooName,
            $fooClientId,
            $fooClientSecret,
            $fooPaymentMethodIdentifier,
            $fooIsSandbox
        );

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($fooChannel)
            ->willReturn($fooPaymentMethodIdentifier);

        $this->localizationHelper->expects($this->exactly(2))
            ->method('getLocalizedValue')
            ->willReturnOnConsecutiveCalls(
                $fooLabel,
                $fooShortLabel
            );

        $actualConfig = $this->factory->createConfig($fooSetting);
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    /**
     * @param string  $name
     * @param string  $clientId
     * @param string  $clientSecret
     * @param string  $label
     * @param string  $shortLabel
     * @param bool    $isSandbox
     * @param Channel $channel
     *
     * @return PayPalExpressSettings
     */
    protected function getSetting($name, $clientId, $clientSecret, $label, $shortLabel, $isSandbox, Channel $channel)
    {
        $setting = new PayPalExpressSettings();
        $setting->setName($name);
        $setting->setClientId($clientId);
        $setting->setClientSecret($clientSecret);

        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setText($label);
        $localizedFallbackValue->setString($label);
        $setting->setLabels(new ArrayCollection([$localizedFallbackValue]));

        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setText($shortLabel);
        $localizedFallbackValue->setString($shortLabel);
        $setting->setShortLabels(new ArrayCollection([$localizedFallbackValue]));

        $setting->setSandboxMode($isSandbox);
        $setting->setChannel($channel);

        return $setting;
    }
}
