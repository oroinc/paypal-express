<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigProviderInterface;

/**
 * Provides {@see PayPalExpressView} for {@see \Oro\Bundle\PaymentBundle\OroPaymentBundle}.
 * Required by {@see \Oro\Bundle\PaymentBundle\OroPaymentBundle}
 */
class PayPalExpressViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var PayPalExpressViewFactoryInterface */
    private $factory;

    /** @var PayPalExpressConfigProviderInterface */
    private $configProvider;

    public function __construct(
        PayPalExpressViewFactoryInterface $factory,
        PayPalExpressConfigProviderInterface $configProvider
    ) {
        $this->factory = $factory;
        $this->configProvider = $configProvider;

        parent::__construct();
    }

    #[\Override]
    protected function buildViews()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addExpressCheckoutView($config);
        }
    }

    protected function addExpressCheckoutView(PayPalExpressConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->factory->create($config)
        );
    }
}
