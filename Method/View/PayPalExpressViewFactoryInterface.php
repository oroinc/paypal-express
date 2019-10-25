<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

/**
 * Public interface of factory {@see PaymentMethodViewInterface}.
 */
interface PayPalExpressViewFactoryInterface
{
    /**
     * @param PayPalExpressConfigInterface $config
     *
     * @return PaymentMethodViewInterface
     */
    public function create(PayPalExpressConfigInterface $config);
}
