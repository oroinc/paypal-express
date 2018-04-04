<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionRegistry;

/**
 * - Responsible for registration of complete payment actions instances and provide access
 * to those action instances.
 * - Responsible for provide registered actions names
 *
 * For more details @see PayPalExpressBundle/Resources/doc/reference/extension-points.md
 */
class CompletePaymentActionRegistry extends PaymentActionRegistry implements ActionNamesProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getActionNames()
    {
        return array_keys($this->paymentActions);
    }
}
