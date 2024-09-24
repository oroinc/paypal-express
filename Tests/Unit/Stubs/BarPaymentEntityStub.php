<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs;

use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;

class BarPaymentEntityStub implements ShippingAwareInterface
{
    public $testShipping;

    #[\Override]
    public function getShippingCost()
    {
        return $this->testShipping;
    }
}
