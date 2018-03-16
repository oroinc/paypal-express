<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\AbstractPaymentAction;

class AuthorizeAndCaptureAction extends AbstractPaymentAction
{
    const NAME = 'authorize_and_capture';

    /**
     * {@inheritdoc}
     */
    public function executeAction(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $route = $this->payPalTransportFacade->getPayPalPaymentRoute($paymentTransaction, $config);

        return [
            'purchaseRedirectUrl' => $route
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
