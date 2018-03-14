<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\Translator;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\LineItemTranslator;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\BarLineItemStub;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\FooLineItemStub;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\ProductStub;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;

class LineItemTranslatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LineItemTranslator
     */
    protected $lineItemTranslator;

    protected function setUp()
    {
        $this->lineItemTranslator = new LineItemTranslator();
    }

    public function testGetPaymentItemInfo()
    {
        $currency = 'USD';
        $name = 'Test Item';
        $quantity = 1;
        $priceAmount = 1.23;

        $expectedPaymentItemInfo = new ItemInfo($name, $currency, $quantity, $priceAmount);

        $lineItem = $this->getLineItem($name, $currency, $priceAmount, $quantity);

        $actualPaymentItemInfo = $this->lineItemTranslator->getPaymentItemInfo($lineItem, $currency);
        $this->assertEquals($expectedPaymentItemInfo, $actualPaymentItemInfo);
    }

    public function testGetPaymentItemInfoShouldReturnFalseIfLineItemIsNotImplementProductLineItemInterface()
    {
        $currency = 'USD';

        $lineItem = new FooLineItemStub();

        $actualPaymentItemInfo = $this->lineItemTranslator->getPaymentItemInfo($lineItem, $currency);
        $this->assertNull($actualPaymentItemInfo);
    }

    public function testGetPaymentItemInfoShouldReturnFalseIfLineItemIsNotImplementPriceAwareInterface()
    {
        $currency = 'USD';

        $lineItem = new BarLineItemStub();

        $actualPaymentItemInfo = $this->lineItemTranslator->getPaymentItemInfo($lineItem, $currency);
        $this->assertNull($actualPaymentItemInfo);
    }

    /**
     * @param string  $name
     * @param string  $currency
     * @param float   $priceAmount
     * @param integer $quantity
     *
     * @return OrderLineItem
     */
    protected function getLineItem($name, $currency, $priceAmount, $quantity)
    {
        $product = new ProductStub();

        $localizedName = new LocalizedFallbackValue();
        $localizedName->setString($name);
        $localizedName->setText($name);
        $product->addName($localizedName);
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency($currency);
        $lineItem->setProduct($product);
        $lineItem->setQuantity($quantity);
        $price = new Price();
        $price->setCurrency($currency);
        $price->setValue($priceAmount);
        $lineItem->setPrice($price);

        return $lineItem;
    }
}
