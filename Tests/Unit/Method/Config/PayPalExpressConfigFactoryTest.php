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
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeAndCaptureAction;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class PayPalExpressConfigFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PayPalExpressConfigFactory
     */
    protected $factory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|IntegrationIdentifierGeneratorInterface
     */
    protected $identifierGenerator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SymmetricCrypterInterface
     */
    protected $encoder;

    protected function setUp()
    {
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);

        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->encoder = $this->createMock(SymmetricCrypterInterface::class);

        $this->factory = new PayPalExpressConfigFactory(
            $this->identifierGenerator,
            $this->localizationHelper,
            $this->encoder
        );
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
        $fooChannel->setName($fooName);

        $fooSetting = $this->getSetting(
            $fooClientId,
            $fooClientSecret,
            $fooLabel,
            $fooShortLabel,
            AuthorizeAndCaptureAction::NAME,
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
            AuthorizeAndCaptureAction::NAME,
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

        $this->encoder->expects($this->exactly(2))
            ->method('decryptData')
            ->willReturnArgument(0);

        $actualConfig = $this->factory->createConfig($fooSetting);
        $this->assertEquals($expectedConfig, $actualConfig);
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $label
     * @param string $shortLabel
     * @param string $paymentAction
     * @param bool $isSandbox
     * @param Channel $channel
     *
     * @return PayPalExpressSettings
     */
    protected function getSetting(
        $clientId,
        $clientSecret,
        $label,
        $shortLabel,
        $paymentAction,
        $isSandbox,
        Channel $channel
    ) {
        $setting = new PayPalExpressSettings();
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
        $setting->setPaymentAction($paymentAction);

        return $setting;
    }
}
