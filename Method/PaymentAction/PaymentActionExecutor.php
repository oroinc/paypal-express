<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

/**
 * Responsible for retrieve concrete payment action instance from registry and execute it
 * Help us to avoid usage of the implicit method calls in PayPalExpressMethod, instead of it we delegate
 * work to appropriate action with explicit interface
 */
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
