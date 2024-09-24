<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\AuthorizeAction;

/**
 * Implementation of "Authorize" action for {@see PaymentTransaction}.
 * It can be be executed when user created a payment on PayPal side.
 */
class AuthorizeOnCompleteAction extends AuthorizeAction
{
    const NAME = 'authorize';

    /**
     * Name is overridden because those names have different meaning
     * parent::getName() - will return payment method action name,
     * but this method will return Action name supported as complete action
     *
     * @return string
     */
    #[\Override]
    public function getName()
    {
        return self::NAME;
    }
}
