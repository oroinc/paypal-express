<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionExecutor;

class PayPalMethodFactory implements PayPalMethodFactoryInterface
{
    /**
     * @var PaymentActionExecutor
     */
    protected $payPalActionExecutor;

    /**
     * @param PaymentActionExecutor $payPalActionExecutor
     */
    public function __construct(PaymentActionExecutor $payPalActionExecutor)
    {
        $this->payPalActionExecutor = $payPalActionExecutor;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PayPalExpressConfigInterface $config)
    {
        return new PayPalMethod($config, $this->payPalActionExecutor);
    }
}
