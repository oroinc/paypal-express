<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

class CaptureAction extends AbstractPaymentAction
{
    /**
     * {@inheritdoc}
     */
    public function executeAction(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        // TODO: Implement executeAction() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return PaymentMethodInterface::CAPTURE;
    }
}
