<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionExecutor;

class PayPalMethodFactory implements PayPalMethodFactoryInterface
{
    /**
     * @var PayPalTransportFacade
     */
    protected $palTransportFacade;

    /**
     * @var PaymentActionExecutor
     */
    protected $payPalActionExecutor;

    /**
     * @param PayPalTransportFacade $palTransportFacade
     * @param PaymentActionExecutor $payPalActionExecutor
     */
    public function __construct(PayPalTransportFacade $palTransportFacade, PaymentActionExecutor $payPalActionExecutor)
    {
        $this->palTransportFacade = $palTransportFacade;
        $this->payPalActionExecutor = $payPalActionExecutor;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PayPalExpressConfigInterface $config)
    {
        return new PayPalMethod($this->palTransportFacade, $config, $this->payPalActionExecutor);
    }
}
