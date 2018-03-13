<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

class PayPalViewFactory implements PayPalViewFactoryInterface
{

    /**
     * @param PayPalExpressConfigInterface $config
     *
     * @return PaymentMethodViewInterface
     */
    public function create(PayPalExpressConfigInterface $config)
    {
        return new PayPalView($config);
    }
}
