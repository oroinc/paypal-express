<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\AuthorizeAction;

class AuthorizeOnCompleteAction extends AuthorizeAction
{
    const NAME = 'authorize';

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
