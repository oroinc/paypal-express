<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\Translator;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\LineItemTranslator;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;

use Symfony\Component\Translation\TranslatorInterface;

class LineItemTranslatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExtractOptionsProvider
     */
    protected $extractOptionsProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var LineItemTranslator
     */
    protected $lineItemTranslator;

    protected function setUp()
    {
        $this->extractOptionsProvider = $this->createMock(ExtractOptionsProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->lineItemTranslator = new LineItemTranslator($this->extractOptionsProvider, $this->translator);
    }

    public function testGetPaymentItems()
    {
        $currency = 'USD';

        $fooName = 'Foo Test Item';
        $expectedFooQuantity = 1;
        $fooQuantity = 1.00;
        $fooPriceAmount = 1.23;
        $expectedFooItemInfo = new ItemInfo($fooName, $currency, $expectedFooQuantity, $fooPriceAmount);

        $barName = 'Bar Test Item';
        $expectedBarQuantity = 2;
        $barQuantity = 2.00;
        $barPriceAmount = 4.25;
        $expectedBarItemInfo = new ItemInfo($barName, $currency, $expectedBarQuantity, $barPriceAmount);

        $expectedPaymentItems = [$expectedFooItemInfo, $expectedBarItemInfo];

        $surcharge = new Surcharge();

        $order = new Order();

        $lineItemOptionsModels = [
            $this->getLineItemOptionModel($fooName, $currency, $fooQuantity, $fooPriceAmount),
            $this->getLineItemOptionModel($barName, $currency, $barQuantity, $barPriceAmount),
        ];
        $this->extractOptionsProvider->expects($this->once())
            ->method('getLineItemPaymentOptions')
            ->with($order)
            ->willReturn($lineItemOptionsModels);

        $paymentItems = $this->lineItemTranslator->getPaymentItems($order, $surcharge, $currency);
        $this->assertEquals($expectedPaymentItems, $paymentItems);

        foreach ($paymentItems as $paymentItem) {
            /**
             * Assert Equals does not support value type check, as a workaround manual assertions was added
             */
            $this->assertInternalType('integer', $paymentItem->getQuantity());
        }
    }

    public function testGetPaymentItemsWillReturnNegativeAmountDiscountItemIfDiscountPresented()
    {
        $currency = 'USD';

        $fooName = 'Foo Test Item';
        $fooQuantity = 1;
        $fooPriceAmount = 1.23;
        $expectedFooItemInfo = new ItemInfo($fooName, $currency, $fooQuantity, $fooPriceAmount);

        $discountItemName = 'Bar Test Item';
        $discountQuantity = 1;
        $discountAmount = -0.02;
        $expectedDiscountItemInfo = new ItemInfo($discountItemName, $currency, $discountQuantity, $discountAmount);

        $expectedPaymentItems = [$expectedFooItemInfo, $expectedDiscountItemInfo];

        $surcharge = new Surcharge();
        $surcharge->setDiscountAmount($discountAmount);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemTranslator::DISCOUNT_ITEM_LABEL)
            ->willReturn($discountItemName);

        $order = new Order();
        $this->extractOptionsProvider->expects($this->once())
            ->method('getLineItemPaymentOptions')
            ->with($order)
            ->willReturn([
                $this->getLineItemOptionModel($fooName, $currency, $fooQuantity, $fooPriceAmount),
            ]);

        $paymentItems = $this->lineItemTranslator->getPaymentItems($order, $surcharge, $currency);
        $this->assertEquals($expectedPaymentItems, $paymentItems);
    }

    /**
     * @param string $name
     * @param string $currency
     * @param float  $quantity
     * @param float  $amount
     *
     * @return LineItemOptionModel
     */
    protected function getLineItemOptionModel($name, $currency, $quantity, $amount)
    {
        $model = new LineItemOptionModel();

        $model->setName($name);
        $model->setCurrency($currency);
        $model->setQty($quantity);
        $model->setCost($amount);

        return $model;
    }
}
