<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\Translator;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\LineItemTranslator;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Symfony\Component\Translation\TranslatorInterface;

class LineItemTranslatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExtractOptionsProvider
     */
    protected $extractOptionsProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
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

    public function testCanGetPaymentItemsForTwoLineItems()
    {
        $currency = 'USD';

        $orderLineItems = [
            $this->createLineItemOptionModel(
                'Foo Test Item',
                $currency,
                1.00,
                1.23
            ),
            $this->createLineItemOptionModel(
                'Bar Test Item',
                $currency,
                2.00,
                4.25
            )
        ];

        $expectedPaymentItemsInfo = [
            $this->createPaymentItemInfo(
                'Foo Test Item',
                $currency,
                1,
                1.23
            ),
            $this->createPaymentItemInfo(
                'Bar Test Item',
                $currency,
                2,
                4.25
            )
        ];

        $order = $this->createOrderWithExpectedLineItems($orderLineItems);

        $actualPaymentItems = $this->lineItemTranslator->getPaymentItems($order, $this->createSurcharge(), $currency);

        $this->assertEquals($expectedPaymentItemsInfo, $actualPaymentItems);
    }

    public function testCanConvertPaymentItemsQuantityToInteger()
    {
        $currency = 'USD';

        $orderLineItems = [
            $this->createLineItemOptionModel(
                'Foo Test Item',
                $currency,
                2.75,
                1.23
            )
        ];

        $expectedPaymentItemsInfo = [
            $this->createPaymentItemInfo(
                'Foo Test Item',
                $currency,
                2,
                1.23
            )
        ];

        $order = $this->createOrderWithExpectedLineItems($orderLineItems);

        $actualPaymentItems = $this->lineItemTranslator->getPaymentItems($order, $this->createSurcharge(), $currency);

        $this->assertEquals($expectedPaymentItemsInfo, $actualPaymentItems);
        $this->assertInternalType('integer', $actualPaymentItems[0]->getQuantity());
    }

    public function testCanConvertDiscountAmountToPaymentItem()
    {
        $currency = 'USD';
        $discountAmount = -0.02;
        $discountItemName = 'Discount';
        $surcharge = $this->createSurchargeWithDiscountAmount($discountAmount, $discountItemName);

        $orderLineItems = [
            $this->createLineItemOptionModel(
                'Foo Test Item',
                $currency,
                2.75,
                1.23
            )
        ];

        $expectedPaymentItemsInfo = [
            $this->createPaymentItemInfo(
                'Foo Test Item',
                $currency,
                2,
                1.23
            ),
            $this->createPaymentItemInfo(
                $discountItemName,
                $currency,
                1,
                $discountAmount
            ),
        ];

        $order = $this->createOrderWithExpectedLineItems($orderLineItems);

        $actualPaymentItems = $this->lineItemTranslator->getPaymentItems($order, $surcharge, $currency);

        $this->assertEquals($expectedPaymentItemsInfo, $actualPaymentItems);
    }

    public function testCanIgnoreTaxLineItemsWithoutCurrency()
    {
        $currency = 'USD';

        $orderLineItems = [
            $this->createLineItemOptionModel(
                'Foo Test Item',
                $currency,
                2.75,
                1.23
            ),
            $this->createLineItemOptionModel(
                'Tax Item',
                null,
                1.00,
                4.25
            )
        ];

        $expectedPaymentItemsInfo = [
            $this->createPaymentItemInfo(
                'Foo Test Item',
                $currency,
                2,
                1.23
            )
        ];

        $order = $this->createOrderWithExpectedLineItems($orderLineItems);

        $actualPaymentItems = $this->lineItemTranslator->getPaymentItems($order, $this->createSurcharge(), $currency);

        $this->assertEquals($expectedPaymentItemsInfo, $actualPaymentItems);
    }

    /**
     * @param string $name
     * @param string $currency
     * @param float  $quantity
     * @param float  $amount
     *
     * @return LineItemOptionModel
     */
    protected function createLineItemOptionModel($name, $currency, $quantity, $amount)
    {
        $model = new LineItemOptionModel();

        $model->setName($name);
        $model->setCurrency($currency);
        $model->setQty($quantity);
        $model->setCost($amount);

        return $model;
    }

    /**
     * @param string $name
     * @param string $currency
     * @param int    $quantity
     * @param float  $amount
     * @return ItemInfo
     */
    protected function createPaymentItemInfo($name, $currency, $quantity, $amount)
    {
        $itemInfo = new ItemInfo(
            $name,
            $currency,
            $quantity,
            $amount
        );

        return $itemInfo;
    }

    /**
     * @param array $lineItemOptionsModels
     * @return Order
     */
    protected function createOrderWithExpectedLineItems(array $lineItemOptionsModels = [])
    {
        $order = new Order();

        $this->extractOptionsProvider->expects($this->once())
            ->method('getLineItemPaymentOptions')
            ->with($order)
            ->willReturn($lineItemOptionsModels);

        return $order;
    }

    /**
     * @return Surcharge
     */
    protected function createSurcharge()
    {
        return new Surcharge();
    }

    /**
     * @param float $discountAmount
     * @param string $discountItemName
     * @return Surcharge
     */
    protected function createSurchargeWithDiscountAmount($discountAmount, $discountItemName)
    {
        $surcharge = $this->createSurcharge();
        $surcharge->setDiscountAmount($discountAmount);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemTranslator::DISCOUNT_ITEM_LABEL)
            ->willReturn($discountItemName);

        return $surcharge;
    }
}
