<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PayPalExpressBundle\Method\PayPalTransportFacadeInterface;

abstract class AbstractPaymentAction implements PaymentActionInterface
{
    /**
     * @var PayPalTransportFacadeInterface
     */
    protected $payPalTransportFacade;

    public function __construct(PayPalTransportFacadeInterface $payPalTransportFacade)
    {
        $this->payPalTransportFacade = $payPalTransportFacade;
    }
}
