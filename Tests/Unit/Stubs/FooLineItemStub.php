<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;

class FooLineItemStub implements PriceAwareInterface
{
    /**
     * @return Price
     */
    public function getPrice()
    {
    }
}
