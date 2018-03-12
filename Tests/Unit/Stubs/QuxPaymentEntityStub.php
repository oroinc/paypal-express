<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs;

use Oro\Bundle\OrderBundle\Model\ShippingAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

class QuxPaymentEntityStub implements ShippingAwareInterface, SubtotalAwareInterface
{
    public $testShipping;

    public $testSubtotal;

    /**
     * {@inheritdoc}
     */
    public function getShippingCost()
    {
        return $this->testShipping;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotal()
    {
        return $this->testSubtotal;
    }
}
