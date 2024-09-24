<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

/**
 * Creates {@see PaymentMethodView} instance.
 */
class PayPalExpressViewFactory implements PayPalExpressViewFactoryInterface
{
    /**
     * @param PayPalExpressConfigInterface $config
     *
     * @return PaymentMethodViewInterface
     */
    #[\Override]
    public function create(PayPalExpressConfigInterface $config)
    {
        return new PayPalExpressView($config);
    }
}
