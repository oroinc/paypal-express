<?php

namespace Oro\Bundle\PayPalExpressBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\Factory\CryptedDataTransformerFactory;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PayPalSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_paypal_express_settings';
    /**
     * @var CryptedDataTransformerFactory
     */
    protected $cryptedDataTransformerFactory;

    /**
     * PayPalSettingsType constructor.
     * @param CryptedDataTransformerFactory $cryptedDataTransformerFactory
     */
    public function __construct(CryptedDataTransformerFactory $cryptedDataTransformerFactory)
    {
        $this->cryptedDataTransformerFactory = $cryptedDataTransformerFactory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('labels', LocalizedFallbackValueCollectionType::NAME, [
                'label'    => 'oro.paypal_express.settings.labels.label',
                'tooltip'  => 'oro.paypal_express.settings.labels.tooltip',
                'required' => true,
                'options'  => ['constraints' => [new NotBlank()]],
            ])
            ->add('shortLabels', LocalizedFallbackValueCollectionType::NAME, [
                'label'    => 'oro.paypal_express.settings.short_labels.label',
                'tooltip'  => 'oro.paypal_express.settings.short_labels.tooltip',
                'required' => true,
                'options'  => ['constraints' => [new NotBlank()]],
            ]);

        $clientIdFieldBuilder = $builder->create(
            'clientId',
            TextType::class,
            [
                'label'    => 'oro.paypal_express.settings.client_id.label',
                'tooltip'  => 'oro.paypal_express.settings.client_id.tooltip',
                'required' => true
            ]
        );
        $builder->add($this->addCryptedTransformer($clientIdFieldBuilder));

        $clientSecretFieldBuilder = $builder->create(
            'clientSecret',
            TextType::class,
            [
                'label'    => 'oro.paypal_express.settings.client_secret.label',
                'tooltip'  => 'oro.paypal_express.settings.client_secret.tooltip',
                'required' => true
            ]
        );
        $builder->add($this->addCryptedTransformer($clientSecretFieldBuilder));

        $builder->add('testMode', CheckboxType::class, [
            'label'    => 'oro.paypal_express.settings.test_mode.label',
            'tooltip'  => 'oro.paypal_express.settings.test_mode.tooltip',
            'required' => false,
        ]);
    }

    /**
     * @param FormBuilderInterface $builder
     *
     * @return FormBuilderInterface
     */
    protected function addCryptedTransformer(FormBuilderInterface $builder)
    {
        $builder->addModelTransformer($this->cryptedDataTransformerFactory->create());

        return $builder;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PayPalExpressSettings::class,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
