<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\Translator;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PayPalBundle\OptionsProvider\OptionsProvider;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\LineItemTranslator;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Symfony\Contracts\Translation\TranslatorInterface;

class LineItemTranslatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|OptionsProvider
     */
    protected $optionsProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var RoundingServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $rounder;

    /**
     * @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currencyFormatter;

    /**
     * @var LineItemTranslator
     */
    protected $lineItemTranslator;

    protected function setUp(): void
    {
        $this->optionsProvider = $this->createMock(OptionsProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->rounder = $this->createMock(RoundingServiceInterface::class);
        $this->rounder->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($amount) {
                return round($amount, 2);
            });
        $this->currencyFormatter = $this->createMock(NumberFormatter::class);

        $this->lineItemTranslator = new LineItemTranslator($this->optionsProvider, $this->translator);
        $this->lineItemTranslator->setRounder($this->rounder);
        $this->lineItemTranslator->setCurrencyFormatter($this->currencyFormatter);
    }

    public function testCanGetPaymentItemsForTwoLineItems()
    {
        $this->rounder->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($amount) {
                return round($amount, 2);
            });
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
        $this->currencyFormatter->expects($this->atLeastOnce())
            ->method('formatCurrency')
            ->willReturnCallback(function ($price, $currency) {
                return $price . ' ' . $currency;
            });

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
                'Foo Test Item - 1.23 USDx2.75',
                $currency,
                1,
                3.38
            )
        ];

        $order = $this->createOrderWithExpectedLineItems($orderLineItems);

        $actualPaymentItems = $this->lineItemTranslator->getPaymentItems($order, $this->createSurcharge(), $currency);

        $this->assertEquals($expectedPaymentItemsInfo, $actualPaymentItems);
        $this->assertIsInt($actualPaymentItems[0]->getQuantity());
    }

    public function testCanConvertPaymentItemsPricePrecisionMoreThanTwo()
    {
        $currency = 'USD';
        $this->currencyFormatter->expects($this->atLeastOnce())
            ->method('formatCurrency')
            ->willReturnCallback(function ($price, $currency) {
                return round($price, 2) . ' ' . $currency;
            });
        $this->rounder->expects($this->atLeastOnce())
            ->method('getPrecision')
            ->willReturn(4);

        $orderLineItems = [
            $this->createLineItemOptionModel(
                'Foo Test Item',
                $currency,
                2,
                1.2363
            ),
            $this->createLineItemOptionModel(
                'Foo Test Item 2',
                $currency,
                1,
                1.2363
            )
        ];

        $expectedPaymentItemsInfo = [
            $this->createPaymentItemInfo(
                'Foo Test Item - 1.24 USDx2',
                $currency,
                1,
                2.47
            ),
            $this->createPaymentItemInfo(
                'Foo Test Item 2',
                $currency,
                1,
                1.24
            )
        ];

        $order = $this->createOrderWithExpectedLineItems($orderLineItems);

        $actualPaymentItems = $this->lineItemTranslator->getPaymentItems($order, $this->createSurcharge(), $currency);

        $this->assertEquals($expectedPaymentItemsInfo, $actualPaymentItems);
        $this->assertIsInt($actualPaymentItems[0]->getQuantity());
    }

    public function testCanConvertDiscountAmountToPaymentItem()
    {
        $this->currencyFormatter->expects($this->atLeastOnce())
            ->method('formatCurrency')
            ->willReturnCallback(function ($price, $currency) {
                return round($price, 2) . ' ' . $currency;
            });
        $this->rounder->expects($this->atLeastOnce())
            ->method('getPrecision')
            ->willReturn(4);

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
                'Foo Test Item - 1.23 USDx2.75',
                $currency,
                1,
                3.38
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
        $this->currencyFormatter->expects($this->atLeastOnce())
            ->method('formatCurrency')
            ->willReturnCallback(function ($price, $currency) {
                return round($price, 2) . ' ' . $currency;
            });
        $this->rounder->expects($this->atLeastOnce())
            ->method('getPrecision')
            ->willReturn(2);
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
                'Foo Test Item - 1.23 USDx2.75',
                $currency,
                1,
                3.38
            )
        ];

        $order = $this->createOrderWithExpectedLineItems($orderLineItems);

        $actualPaymentItems = $this->lineItemTranslator->getPaymentItems($order, $this->createSurcharge(), $currency);

        $this->assertEquals($expectedPaymentItemsInfo, $actualPaymentItems);
    }

    public function testCreateTotalLineItem()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with(LineItemTranslator::TOTAL_ITEM_LABEL)
            ->willReturn('TOTAL');

        $item = $this->lineItemTranslator->createTotalLineItem('USD', 1.123);
        $this->assertEquals('TOTAL', $item->getName());
        $this->assertEquals(1.12, $item->getPrice());
        $this->assertEquals('USD', $item->getCurrency());
        $this->assertEquals(1, $item->getQuantity());
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

        $this->optionsProvider->expects($this->once())
            ->method('getLineItemOptions')
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
