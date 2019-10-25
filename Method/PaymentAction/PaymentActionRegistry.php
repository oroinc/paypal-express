<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\LogicException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;

/**
 * Registers instances of {@see PaymentActionInterface} and can return them by name.
 *
 * For more details check documentation.
 *
 * @see Resources/doc/reference/extension-points.md
 */
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
     * @param iterable|PaymentActionInterface[] $paymentActions
     */
    public function __construct(ExceptionFactory $exceptionFactory, iterable $paymentActions)
    {
        $this->exceptionFactory = $exceptionFactory;
        $this->registerActions($paymentActions);
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
     * @param iterable|PaymentActionInterface[] $paymentActions
     */
    public function registerActions(iterable $paymentActions): void
    {
        foreach ($paymentActions as $paymentAction) {
            $this->registerAction($paymentAction);
        }
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
