<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class PayPalMethod implements PaymentMethodInterface
{
    const COMPLETE = 'complete';

    /**
     * @param string             $action
     * @param PaymentTransaction $paymentTransaction
     * @return array
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        // TODO: Implement execute() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * @todo: validate currency and amount
     * @param PaymentContextInterface $context
     *
     * @return bool
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        return true;
    }

    /**
     * @param string $actionName
     * @return bool
     */
    public function supports($actionName)
    {
        if ($actionName === self::VALIDATE) {
            return false;
        }

        return in_array(
            $actionName,
            [self::PURCHASE, self::COMPLETE],
            true
        );
    }
}
