<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\Translator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedCurrencyException;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedValueException;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\LineItemTranslator;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator;
use Oro\Bundle\PayPalExpressBundle\Provider\TaxProvider;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\BarPaymentEntityStub;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\BazPaymentEntityStub;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\FooLineItemStub;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\FooPaymentEntityStub;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\QuxPaymentEntityStub;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;
use Symfony\Component\Routing\RouterInterface;

class PaymentTransactionTranslatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentTransactionTranslator
     */
    protected $translator;

    /**
     * @var SupportedCurrenciesHelper
     */
    protected $supportedCurrenciesHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LineItemTranslator
     */
    protected $lineItemTranslator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TaxProvider
     */
    protected $taxProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface
     */
    protected $router;

    protected function setUp()
    {
        $this->supportedCurrenciesHelper = new SupportedCurrenciesHelper();

        $this->lineItemTranslator = $this->createMock(LineItemTranslator::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->taxProvider = $this->createMock(TaxProvider::class);

        $this->router = $this->createMock(RouterInterface::class);

        $this->translator = new PaymentTransactionTranslator(
            $this->supportedCurrenciesHelper,
            $this->lineItemTranslator,
            $this->doctrineHelper,
            $this->taxProvider,
            $this->router
        );
    }

    public function testGetPaymentInfo()
    {
        $totalAmount = 25.39;
        $currency = 'USD';
        $shipping = 12.35;
        $tax = 1.04;
        $subtotal = 12;

        $fooItemName = 'foo item';
        $fooQuantity = 2;
        $fooPrice = 6;
        $barItemName = 'bar item';
        $barQuantity = 1;
        $barPrice = 6;

        $fooLineItem = new OrderLineItem();
        $barLineItem = new OrderLineItem();
        $paymentEntity = $this->getOrder($currency, $shipping, $subtotal, [$fooLineItem, $barLineItem]);
        $paymentEntityId = 42;

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->with($paymentEntity)
            ->willReturn($tax);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(Order::class, $paymentEntityId)
            ->willReturn($paymentEntity);

        $fooPaymentItemInfo = new ItemInfo($fooItemName, $currency, $fooQuantity, $fooPrice);
        $barPaymentItemInfo = new ItemInfo($barItemName, $currency, $barQuantity, $barPrice);

        $this->lineItemTranslator->expects($this->exactly(2))
            ->method('getPaymentItemInfo')
            ->willReturnMap(
                [
                    [$fooLineItem, $currency, $fooPaymentItemInfo],
                    [$barLineItem, $currency, $barPaymentItemInfo],
                ]
            );

        $expectedPaymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            $shipping,
            $tax,
            $subtotal,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            [
                $fooPaymentItemInfo,
                $barPaymentItemInfo
            ]
        );

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);

        $this->assertEquals($expectedPaymentInfo, $actualPaymentInfo);
    }

    public function testGetPaymentInfoWillIgnoreLineItemsWhichCouldNotBeTranslated()
    {
        $totalAmount = 25.39;
        $currency = 'USD';
        $shipping = 12.35;
        $tax = 1.04;
        $subtotal = 12;

        $fooLineItem = new FooLineItemStub();
        $barLineItem = new FooLineItemStub();
        $paymentEntity = new FooPaymentEntityStub();
        $paymentEntity->setEstimatedShippingCostAmount($shipping);
        $paymentEntity->setCurrency($currency);
        $paymentEntity->setSubtotal($subtotal);
        $paymentEntity->testLineItems = [$fooLineItem, $barLineItem];

        $paymentEntityId = 42;

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->with($paymentEntity)
            ->willReturn($tax);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(FooPaymentEntityStub::class, $paymentEntityId)
            ->willReturn($paymentEntity);

        $this->lineItemTranslator->expects($this->exactly(2))
            ->method('getPaymentItemInfo')
            ->willReturnMap(
                [
                    [$fooLineItem, $currency, null],
                    [$barLineItem, $currency, null],
                ]
            );

        $expectedPaymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            $shipping,
            $tax,
            $subtotal,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            []
        );

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);

        $this->assertEquals($expectedPaymentInfo, $actualPaymentInfo);
    }

    public function testGetPaymentInfoWillWorkCorrectlyEvenIfPaymentEntityDoesNotSupportLineItems()
    {
        $totalAmount = 25.39;
        $currency = 'USD';
        $shipping = 12.35;
        $tax = 1.04;
        $subtotal = 12;


        $paymentEntity = new QuxPaymentEntityStub();
        $paymentEntity->testSubtotal = $subtotal;
        $paymentEntity->testShipping = $shipping;

        $paymentEntityId = 42;

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->with($paymentEntity)
            ->willReturn($tax);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(QuxPaymentEntityStub::class, $paymentEntityId)
            ->willReturn($paymentEntity);

        $this->lineItemTranslator->expects($this->never())
            ->method('getPaymentItemInfo');

        $expectedPaymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            $shipping,
            $tax,
            $subtotal,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            []
        );

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);

        $this->assertEquals($expectedPaymentInfo, $actualPaymentInfo);
    }

    public function testGetPaymentInfoWillUseZeroAsSubtotalValueIfItIsNotSupportedByPaymentEntity()
    {
        $totalAmount = 25.39;
        $currency = 'USD';
        $shipping = 12.35;
        $tax = 1.04;

        $paymentEntity = new BarPaymentEntityStub();
        $paymentEntity->testShipping = $shipping;

        $paymentEntityId = 42;

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->with($paymentEntity)
            ->willReturn($tax);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(BarPaymentEntityStub::class, $paymentEntityId)
            ->willReturn($paymentEntity);

        $expectedPaymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            $shipping,
            $tax,
            0,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            []
        );

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);

        $this->assertEquals($expectedPaymentInfo, $actualPaymentInfo);
    }

    public function testGetPaymentInfoWillUseZeroAsShippingValueIfItIsNotSupportedByPaymentEntity()
    {
        $totalAmount = 25.39;
        $currency = 'USD';
        $tax = 1.04;
        $subtotal = 12;

        $paymentEntity = new BazPaymentEntityStub();
        $paymentEntity->testSubtotal = $subtotal;

        $paymentEntityId = 42;

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $this->taxProvider->expects($this->once())
            ->method('getTax')
            ->with($paymentEntity)
            ->willReturn($tax);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(BazPaymentEntityStub::class, $paymentEntityId)
            ->willReturn($paymentEntity);

        $expectedPaymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            0,
            $tax,
            $subtotal,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            []
        );

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);

        $this->assertEquals($expectedPaymentInfo, $actualPaymentInfo);
    }

    public function testGetPaymentInfoWillThrowAnExceptionIfUnsupportedCurrencyUsed()
    {
        $totalAmount = 25.39;
        $currency = 'Unknown Currency';
        $shipping = 12.35;
        $tax = 1.04;
        $subtotal = 12;

        $paymentEntity = $this->getOrder($currency, $shipping, $subtotal, []);
        $paymentEntityId = 42;

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $this->taxProvider->expects($this->never())
            ->method('getTax')
            ->with($paymentEntity)
            ->willReturn($tax);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntity')
            ->with(Order::class, $paymentEntityId)
            ->willReturn($paymentEntity);

        $this->lineItemTranslator->expects($this->never())
            ->method('getPaymentItemInfo');

        $this->expectException(UnsupportedCurrencyException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Currency "%s" is not supported. Only next currencies are supported: "%s"',
                $currency,
                implode($this->supportedCurrenciesHelper->getSupportedCurrencyCodes())
            )
        );

        $this->translator->getPaymentInfo($paymentTransaction);
    }

    public function testGetPaymentInfoWillThrowAnExceptionIfAmountContainsDecimalButCurrencyDoesNotAllowDecimals()
    {
        $totalAmount = 25.39;
        $currency = 'JPY';
        $shipping = 12.35;
        $tax = 1.04;
        $subtotal = 12;

        $paymentEntity = $this->getOrder($currency, $shipping, $subtotal, []);
        $paymentEntityId = 42;

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $this->taxProvider->expects($this->never())
            ->method('getTax')
            ->with($paymentEntity)
            ->willReturn($tax);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntity')
            ->with(Order::class, $paymentEntityId)
            ->willReturn($paymentEntity);

        $this->lineItemTranslator->expects($this->never())
            ->method('getPaymentItemInfo');

        $this->expectException(UnsupportedValueException::class);
        $this->expectExceptionMessage(
            sprintf('Decimal amount "%s" is not supported for currency "%s"', $totalAmount, $currency)
        );

        $this->translator->getPaymentInfo($paymentTransaction);
    }

    /**
     * @param string $currency
     * @param float  $shipping
     * @param float  $subtotal
     * @param array  $lineItems
     *
     * @return Order
     */
    protected function getOrder($currency, $shipping, $subtotal, array $lineItems)
    {
        $order = new Order();
        $order->setEstimatedShippingCostAmount($shipping);
        $order->setCurrency($currency);
        $order->setSubtotal($subtotal);
        foreach ($lineItems as $lineItem) {
            $order->addLineItem($lineItem);
        }

        return $order;
    }

    /**
     * @param string $currency
     * @param float $totalAmount
     * @param object $paymentEntity
     * @param int $paymentEntityId
     *
     * @return PaymentTransaction
     */
    protected function getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId)
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setCurrency($currency);
        $paymentTransaction->setAmount($totalAmount);
        $paymentTransaction->setEntityClass(get_class($paymentEntity));
        $paymentTransaction->setEntityIdentifier($paymentEntityId);

        return $paymentTransaction;
    }
}
