<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionRegistry;

/**
 * Registry and name provider for complete payment actions. As a registry this class used to get those actions
 * by their names.
 *
 * For more details check link to documentation.
 *
 * @see Resources/doc/reference/extension-points.md
 */
class CompletePaymentActionRegistry extends PaymentActionRegistry implements ActionNamesProviderInterface
{
    #[\Override]
    public function getActionNames()
    {
        return array_keys($this->paymentActions);
    }
}
