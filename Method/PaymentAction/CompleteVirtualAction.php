<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionRegistry;

class CompleteVirtualAction implements PaymentActionInterface
{
    const NAME = 'complete';

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
     * {@inheritdoc}
     */
    public function executeAction(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $paymentAction = $this->registry->getPaymentAction($config->getPaymentAction());

        return $paymentAction->executeAction($paymentTransaction, $config);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
