<?php

namespace Oro\Bundle\PayPalExpressBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\ActionNamesProviderInterface;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\Factory\CryptedDataTransformerFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Form type for {@see PayPalExpressSettings}
 */
class PayPalExpressSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_paypal_express_settings';

    /**
     * @var CryptedDataTransformerFactory
     */
    protected $cryptedDataTransformerFactory;

    /**
     * @var ActionNamesProviderInterface
     */
    protected $actionsProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param CryptedDataTransformerFactory $cryptedDataTransformerFactory
     * @param ActionNamesProviderInterface  $actionsProvider
     * @param TranslatorInterface           $translator
     */
    public function __construct(
        CryptedDataTransformerFactory $cryptedDataTransformerFactory,
        ActionNamesProviderInterface $actionsProvider,
        TranslatorInterface $translator
    ) {
        $this->cryptedDataTransformerFactory = $cryptedDataTransformerFactory;
        $this->actionsProvider               = $actionsProvider;
        $this->translator                    = $translator;
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
            ->add(
                'paymentAction',
                ChoiceType::class,
                [
                    'choices'           => $this->actionsProvider->getActionNames(),
                    'choice_label'      => function ($action, $key) {
                        return $this->translator->trans(
                            sprintf('oro.paypal_express.settings.payment_action.%s', $action)
                        );
                    },
                    'label'             => 'oro.paypal_express.settings.payment_action.label',
                    'tooltip'           => 'oro.paypal_express.settings.payment_action.tooltip',
                    'required'          => true,
                ]
            )->add(
                'labels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label'    => 'oro.paypal_express.settings.labels.label',
                    'tooltip'  => 'oro.paypal_express.settings.labels.tooltip',
                    'required' => true,
                    'entry_options'  => ['constraints' => [new NotBlank(), new Length(['max' => 255])]],
                ]
            )->add(
                'shortLabels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label'    => 'oro.paypal_express.settings.short_labels.label',
                    'tooltip'  => 'oro.paypal_express.settings.short_labels.tooltip',
                    'required' => true,
                    'entry_options'  => ['constraints' => [new NotBlank(), new Length(['max' => 255])]],
                ]
            );

        $clientIdFieldBuilder = $builder->create(
            'clientId',
            TextType::class,
            [
                'label'    => 'oro.paypal_express.settings.client_id.label',
                'tooltip'  => 'oro.paypal_express.settings.client_id.tooltip',
                'required' => true,
                'constraints' => [new NotBlank()]
            ]
        );
        $builder->add($this->addCryptedTransformer($clientIdFieldBuilder));

        $clientSecretFieldBuilder = $builder->create(
            'clientSecret',
            TextType::class,
            [
                'label'    => 'oro.paypal_express.settings.client_secret.label',
                'tooltip'  => 'oro.paypal_express.settings.client_secret.tooltip',
                'required' => true,
                'constraints' => [new NotBlank()]
            ]
        );
        $builder->add($this->addCryptedTransformer($clientSecretFieldBuilder));

        $builder->add(
            'sandboxMode',
            CheckboxType::class,
            [
                'label'    => 'oro.paypal_express.settings.sandbox_mode.label',
                'tooltip'  => 'oro.paypal_express.settings.sandbox_mode.tooltip',
                'required' => false
            ]
        );
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
