<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalAwareInterface;

class BazPaymentEntityStub implements SubtotalAwareInterface
{
    public $testSubtotal;

    /**
     * {@inheritdoc}
     */
    public function getSubtotal()
    {
        return $this->testSubtotal;
    }
}
