<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

/**
 * Delegates execution of payment action using its name in {@see PaymentActionRegistry}.
 */
class PaymentActionExecutor
{
    /**
     * @var PaymentActionRegistry
     */
    protected $registry;

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
