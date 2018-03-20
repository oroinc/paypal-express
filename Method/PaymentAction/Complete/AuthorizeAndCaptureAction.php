<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
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
        $paymentTransaction->setAction($this->getName());

        try {
            $this->payPalTransportFacade->executePayPalPayment($paymentTransaction, $config);
            $this->payPalTransportFacade->authorizePayment($paymentTransaction, $config);
            $this->payPalTransportFacade->capturePayment($paymentTransaction, $paymentTransaction, $config);
            $paymentTransaction
                ->setSuccessful(true)
                ->setActive(false);

            return ['successful' => true];
        } catch (ExceptionInterface $e) {
            $paymentTransaction
                ->setSuccessful(false)
                ->setActive(false);

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
