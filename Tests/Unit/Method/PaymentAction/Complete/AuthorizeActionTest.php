<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction\Complete;

use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeOnCompleteAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction\AuthorizeActionTest as ParentAuthorizeActionTest;

class AuthorizeActionTest extends ParentAuthorizeActionTest
{
    #[\Override]
    protected function createPaymentAction(): PaymentActionInterface
    {
        return new AuthorizeOnCompleteAction($this->facade, $this->logger);
    }
}
