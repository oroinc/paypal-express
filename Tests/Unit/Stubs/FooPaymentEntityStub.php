<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs;

use Oro\Bundle\OrderBundle\Entity\Order;

class FooPaymentEntityStub extends Order
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
