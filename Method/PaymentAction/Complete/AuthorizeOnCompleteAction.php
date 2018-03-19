<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\AbstractPaymentAction;

class AuthorizeOnCompleteAction extends AbstractPaymentAction
{
    const NAME = 'authorize';

    /**
     * {@inheritdoc}
     */
    public function executeAction(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $this->payPalTransportFacade->executePayPalPayment($paymentTransaction, $config);
        $this->payPalTransportFacade->authorizePayment($paymentTransaction, $config);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
