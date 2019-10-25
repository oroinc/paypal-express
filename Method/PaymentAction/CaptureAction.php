<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

/**
 * Executes capture action for {@see PaymentTransaction}.
 */
class CaptureAction extends AbstractPaymentAction
{
    /**
     * {@inheritdoc}
     */
    public function executeAction(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $paymentTransaction->setAction($this->getName());

        try {
            if (!$paymentTransaction->getSourcePaymentTransaction()) {
                return [
                    'successful' => false,
                    'message' => 'oro.paypal_express.error_message.capture_action.source_payment_transaction_not_found'
                ];
            }
            $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();

            $this->payPalTransportFacade->capturePayment($paymentTransaction, $sourceTransaction, $config);
            $paymentTransaction
                ->setSuccessful(true)
                ->setActive(false);

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
        return PaymentMethodInterface::CAPTURE;
    }
}
