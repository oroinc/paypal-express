<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Method\Provider\AbstractPaymentMethodProvider;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigProviderInterface;

class PayPalMethodProvider extends AbstractPaymentMethodProvider
{
    /**
     * @var PayPalMethodFactoryInterface
     */
    protected $payPalMethodFactory;

    /**
     * @var PayPalExpressConfigProviderInterface
     */
    protected $payPalExpressConfigProvider;

    /**
     * @param PayPalMethodFactoryInterface         $payPalMethodFactory
     * @param PayPalExpressConfigProviderInterface $payPalExpressConfigProvider
     */
    public function __construct(
        PayPalMethodFactoryInterface $payPalMethodFactory,
        PayPalExpressConfigProviderInterface $payPalExpressConfigProvider
    ) {
        $this->payPalMethodFactory         = $payPalMethodFactory;
        $this->payPalExpressConfigProvider = $payPalExpressConfigProvider;

        parent::__construct();
    }

    /**
     * Save methods to $methods property
     */
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
