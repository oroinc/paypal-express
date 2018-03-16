<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

class PurchaseAction extends AbstractPaymentAction
{

    /**
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $config
     *
     * @return array
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
        return PaymentMethodInterface::PURCHASE;
    }
}
