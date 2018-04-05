<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

/**
 * Execute authorize action for {@see PaymentTransaction}.
 */
class AuthorizeAction extends AbstractPaymentAction
{
    /**
     * {@inheritdoc}
     */
    public function executeAction(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $paymentTransaction->setAction($this->getName());

        try {
            $this->payPalTransportFacade->executePayPalPayment($paymentTransaction, $config);
            $this->payPalTransportFacade->authorizePayment($paymentTransaction, $config);
            $paymentTransaction
                ->setSuccessful(true)
                ->setActive(true);

            return ['successful' => true];
        } catch (\Throwable $e) {
            $this->handlePaymentTransactionError($paymentTransaction, $e);

            return ['successful' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return PaymentMethodInterface::AUTHORIZE;
    }
}
