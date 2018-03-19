<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionRegistry;

class CompletePaymentActionRegistry extends PaymentActionRegistry
{
    /**
     * @return string[]
     */
    public function getRegisteredActions()
    {
        return array_keys($this->paymentActions);
    }
}
