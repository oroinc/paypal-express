<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

class PaymentActionExecutor
{
    /**
     * @var PaymentActionRegistry
     */
    protected $registry;

    /**
     * @param PaymentActionRegistry $registry
     */
    public function __construct(PaymentActionRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string                       $action
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $config
     *
     * @return array
     */
    public function executeAction($action, PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        if (!$this->isActionSupported($action)) {
            throw new RuntimeException('Payment Action is not supported');
        }

        $concreteAction = $this->registry->getPaymentAction($action);

        return $concreteAction->executeAction($paymentTransaction, $config);
    }

    /**
     * @param string $action
     *
     * @return bool
     */
    public function isActionSupported($action)
    {
        return $this->registry->isActionSupported($action);
    }
}
