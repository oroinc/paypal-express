<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

interface ActionsProviderInterface
{
    /**
     * @return string[]
     */
    public function getRegisteredActions();
}
