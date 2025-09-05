<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

/**
 * Executes purchase action for {@see PaymentTransaction}.
 */
class PurchaseAction extends AbstractPaymentAction
{
    const PAYMENT_TRANSACTION_ACTION_NAME = 'create_payment';

    #[\Override]
    public function executeAction(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $paymentTransaction->setAction(self::PAYMENT_TRANSACTION_ACTION_NAME);

        try {
            $route = $this->payPalTransportFacade->getPayPalPaymentRoute($paymentTransaction, $config);
            $paymentTransaction
                ->setSuccessful(true)
                ->setActive(true);

            return ['purchaseRedirectUrl' => $route];
        } catch (\Throwable $e) {
            $this->handlePaymentTransactionError($paymentTransaction, $e);

            return [];
        }
    }

    #[\Override]
    public function getName()
    {
        return PaymentMethodInterface::PURCHASE;
    }
}
