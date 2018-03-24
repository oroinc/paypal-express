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
        $paymentTransaction->setAction($this->getName());

        try {
            if (!$paymentTransaction->getSourcePaymentTransaction()) {
                return [
                    'successful' => false,
                    'message' => 'Could not capture payment, transaction with approved payment not found'
                ];
            }
            $sourceTransaction = $paymentTransaction->getSourcePaymentTransaction();

            $this->payPalTransportFacade->capturePayment($paymentTransaction, $sourceTransaction, $config);
            $paymentTransaction
                ->setSuccessful(true)
                ->setActive(false);

            return ['successful' => true];
        } catch (\Throwable $e) {
            $this->handleError($paymentTransaction, $e);

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
