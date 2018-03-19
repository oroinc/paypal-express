<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PayPalExpressBundle\Exception\LogicException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;

class PaymentActionRegistry
{
    /**
     * @var PaymentActionInterface[]
     */
    protected $paymentActions = [];

    /**
     * @param string $paymentActionName
     *
     * @return PaymentActionInterface
     */
    public function getPaymentAction($paymentActionName)
    {
        if (!$this->isActionSupported($paymentActionName)) {
            throw new RuntimeException('Payment Action is not supported');
        }

        return $this->paymentActions[$paymentActionName];
    }

    /**
     * @param PaymentActionInterface $paymentAction
     */
    public function registerAction(PaymentActionInterface $paymentAction)
    {
        $paymentActionName = $paymentAction->getName();

        if (isset($this->paymentActions[$paymentActionName])) {
            throw new LogicException('Payment Action with the same name is already registered');
        }

        $this->paymentActions[$paymentActionName] = $paymentAction;
    }

    /**
     * @param string $paymentActionName
     *
     * @return bool
     */
    public function isActionSupported($paymentActionName)
    {
        return isset($this->paymentActions[$paymentActionName]);
    }
}
