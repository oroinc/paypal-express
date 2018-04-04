<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

/**
 * Responsible for creation of @see PaymentMethodView instances
 */
class PayPalExpressViewFactory implements PayPalExpressViewFactoryInterface
{
    /**
     * @param PayPalExpressConfigInterface $config
     *
     * @return PaymentMethodViewInterface
     */
    public function create(PayPalExpressConfigInterface $config)
    {
        return new PayPalExpressView($config);
    }
}
