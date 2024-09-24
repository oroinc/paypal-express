<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigProviderInterface;

/**
 * Provides instances of {@see PayPalExpressMethod}. Required by {@see \Oro\Bundle\PaymentBundle\OroPaymentBundle}
 */
class PayPalExpressMethodProvider extends AbstractPaymentMethodProvider
{
    /**
     * @var PayPalExpressMethodFactoryInterface
     */
    protected $payPalMethodFactory;

    /**
     * @var PayPalExpressConfigProviderInterface
     */
    protected $payPalExpressConfigProvider;

    public function __construct(
        PayPalExpressMethodFactoryInterface $payPalMethodFactory,
        PayPalExpressConfigProviderInterface $payPalExpressConfigProvider
    ) {
        $this->payPalMethodFactory         = $payPalMethodFactory;
        $this->payPalExpressConfigProvider = $payPalExpressConfigProvider;

        parent::__construct();
    }

    /**
     * Save methods to $methods property
     */
    #[\Override]
    protected function collectMethods()
    {
        $configs = $this->payPalExpressConfigProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addMethod(
                $config->getPaymentMethodIdentifier(),
                $this->payPalMethodFactory->create($config)
            );
        }
    }
}
