<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\AbstractPaymentAction;

/**
 * Responsible for execute Authorize And Capture complete Payment Action
 */
class AuthorizeAndCaptureAction extends AbstractPaymentAction
{
    const NAME = 'authorize_and_capture';

    /**
     * {@inheritdoc}
     */
    public function executeAction(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        /**
         * Should be the one of success payment statuses to avoid incorrect status in payment entity
         * @see \Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider::getStatusByEntityAndTransactions
         */
        $paymentTransaction->setAction(PaymentMethodInterface::CAPTURE);

        try {
            $this->payPalTransportFacade->executePayPalPayment($paymentTransaction, $config);
            $this->payPalTransportFacade->capturePayment($paymentTransaction, $paymentTransaction, $config);
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
        return self::NAME;
    }
}
