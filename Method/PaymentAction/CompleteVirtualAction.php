<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\CompletePaymentActionRegistry;

/**
 * Delegates execution of "complete payment action" depending which action is configured in
 * {@see PayPalExpressConfigInterface}.
 *
 * For more details check documentation.
 *
 * @see Resources/doc/reference/extension-points.md
 */
class CompleteVirtualAction implements PaymentActionInterface
{
    const NAME = 'complete';

    /**
     * @var CompletePaymentActionRegistry
     */
    protected $completePaymentActionRegistry;

    /**
     * @param CompletePaymentActionRegistry $completePaymentActionRegistry
     */
    public function __construct(CompletePaymentActionRegistry $completePaymentActionRegistry)
    {
        $this->completePaymentActionRegistry = $completePaymentActionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function executeAction(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $paymentAction = $this->completePaymentActionRegistry->getPaymentAction($config->getPaymentAction());

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
