<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Form\Type\PayPalExpressSettingsType;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\ActionNamesProviderInterface;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\Factory\CryptedDataTransformerFactory;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PayPalExpressSettingsTypeTest extends FormIntegrationTestCase
{
    private DataTransformerInterface&MockObject $dataTransformer;

    private PayPalExpressSettingsType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->dataTransformer = $this->createMock(DataTransformerInterface::class);

        $cryptedDataTransformerFactory = $this->createMock(CryptedDataTransformerFactory::class);
        $cryptedDataTransformerFactory->expects(self::any())
            ->method('create')
            ->willReturn($this->dataTransformer);

        $actionsProvider = $this->createMock(ActionNamesProviderInterface::class);
        $actionsProvider->expects(self::any())
            ->method('getActionNames')
            ->willReturn(['authorize', 'capture']);

        $this->formType = new PayPalExpressSettingsType(
            $cryptedDataTransformerFactory,
            $actionsProvider,
            $this->createMock(TranslatorInterface::class)
        );

        parent::setUp();
    }

    /**
     * The parent implementation resolves the validation.yml path by searching for "Bundle" in the entity file path.
     * Since PayPalExpressBundle classes reside in "package/commerce-paypal-express/"
     * (without "Bundle" in the directory structure), the automatic resolution fails.
     * This override provides the correct path explicitly.
     */
    #[\Override]
    protected function getConfigFile(string $class): ?string
    {
        if ($class === PayPalExpressSettings::class) {
            return dirname(__DIR__, 4) . '/Resources/config/validation.yml';
        }

        return parent::getConfigFile($class);
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)],
                ]
            ),
            $this->getValidatorExtension(true),
        ];
    }

    public function testSubmitValid(): void
    {
        $this->dataTransformer->expects(self::any())
            ->method('reverseTransform')
            ->willReturnArgument(0);

        $submitData = [
            'paymentAction' => 'authorize',
            'clientId' => 'clientId',
            'clientSecret' => 'clientSecret',
        ];

        $settings = new PayPalExpressSettings();
        $settings->setLabels(new ArrayCollection());
        $settings->setShortLabels(new ArrayCollection());
        $form = $this->factory->create(PayPalExpressSettingsType::class, $settings);
        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertTrue($form->isValid());
    }

    /**
     * @dataProvider submitWithLongValuesProvider
     */
    public function testSubmitWithTooLongValues(array $override): void
    {
        $this->dataTransformer->expects(self::any())
            ->method('reverseTransform')
            ->willReturnArgument(0);

        $submitData = array_replace_recursive([
            'paymentAction' => 'authorize',
            'clientId' => 'clientId',
            'clientSecret' => 'clientSecret',
        ], $override);

        $settings = new PayPalExpressSettings();
        $settings->setLabels(new ArrayCollection());
        $settings->setShortLabels(new ArrayCollection());
        $form = $this->factory->create(PayPalExpressSettingsType::class, $settings);
        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($form->isValid());
    }

    public function submitWithLongValuesProvider(): array
    {
        return [
            'clientId too long' => [['clientId' => str_repeat('a', 256)]],
            'clientSecret too long' => [['clientSecret' => str_repeat('a', 256)]],
        ];
    }

    public function testGetBlockPrefix(): void
    {
        self::assertSame(PayPalExpressSettingsType::BLOCK_PREFIX, $this->formType->getBlockPrefix());
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefaults')
            ->with(['data_class' => PayPalExpressSettings::class]);

        $this->formType->configureOptions($resolver);
    }
}
