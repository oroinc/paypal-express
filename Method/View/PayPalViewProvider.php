<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigProviderInterface;

class PayPalViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var PayPalViewFactoryInterface */
    private $factory;

    /** @var PayPalExpressConfigProviderInterface */
    private $configProvider;

    /**
     * @param PayPalViewFactoryInterface $factory
     * @param PayPalExpressConfigProviderInterface $configProvider
     */
    public function __construct(
        PayPalViewFactoryInterface $factory,
        PayPalExpressConfigProviderInterface $configProvider
    ) {
        $this->factory = $factory;
        $this->configProvider = $configProvider;

        parent::__construct();
    }

    protected function buildViews()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addExpressCheckoutView($config);
        }
    }

    /**
     * @param PayPalExpressConfigInterface $config
     */
    protected function addExpressCheckoutView(PayPalExpressConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
