<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs;

use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;

class BarPaymentEntityStub implements ShippingAwareInterface
{
    public $testShipping;

    /**
     * {@inheritdoc}
     */
    public function getShippingCost()
    {
        return $this->testShipping;
    }
}
