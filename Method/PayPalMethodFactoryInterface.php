<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

interface PayPalMethodFactoryInterface
{

    /**
     * @param PayPalExpressConfigInterface $config
     *
     * @return PaymentMethodInterface
     */
    public function create(PayPalExpressConfigInterface $config);
}
