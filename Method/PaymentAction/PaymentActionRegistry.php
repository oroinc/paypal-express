<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\LogicException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;

class PaymentActionRegistry
{
    /**
     * @var ExceptionFactory
     */
    protected $exceptionFactory;

    /**
     * @var PaymentActionInterface[]
     */
    protected $paymentActions = [];

    /**
     * @param ExceptionFactory $exceptionFactory
     */
    public function __construct(ExceptionFactory $exceptionFactory)
    {
        $this->exceptionFactory = $exceptionFactory;
    }

    /**
     * @param string $paymentActionName
     *
     * @return PaymentActionInterface
     * @throws RuntimeException
     */
    public function getPaymentAction($paymentActionName)
    {
        if (!$this->isActionSupported($paymentActionName)) {
            $exception = $this->exceptionFactory
                ->createRuntimeException(sprintf('Payment Action "%s" is not supported', $paymentActionName));
            throw $exception;
        }

        return $this->paymentActions[$paymentActionName];
    }

    /**
     * @param PaymentActionInterface $paymentAction
     *
     * @throws LogicException
     */
    public function registerAction(PaymentActionInterface $paymentAction)
    {
        $paymentActionName = $paymentAction->getName();

        if (isset($this->paymentActions[$paymentActionName])) {
            $exception = $this->exceptionFactory
                ->createLogicException('Payment Action with the same name is already registered');

            throw $exception;
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
