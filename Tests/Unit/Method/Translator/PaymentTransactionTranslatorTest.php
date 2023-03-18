<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\Translator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedCurrencyException;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedValueException;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\LineItemTranslator;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator;
use Oro\Bundle\PayPalExpressBundle\Provider\TaxProvider;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\BarPaymentEntityStub;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Stubs\QuxPaymentEntityStub;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PaymentTransactionTranslatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentTransactionTranslator */
    private $translator;

    /** @var SupportedCurrenciesHelper */
    private $supportedCurrenciesHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LineItemTranslator */
    private $lineItemTranslator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TaxProvider */
    private $taxProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SurchargeProvider */
    private $surchargeProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|RouterInterface */
    private $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExceptionFactory */
    private $exceptionFactory;

    protected function setUp(): void
    {
        $this->supportedCurrenciesHelper = new SupportedCurrenciesHelper();
        $this->lineItemTranslator = $this->createMock(LineItemTranslator::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->taxProvider = $this->createMock(TaxProvider::class);
        $this->surchargeProvider = $this->createMock(SurchargeProvider::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->exceptionFactory = $this->createMock(ExceptionFactory::class);

        $this->lineItemTranslator->expects($this->any())
            ->method('roundForPayPal')
            ->willReturnCallback(function ($amount) {
                return round($amount, 2);
            });

        $this->translator = new PaymentTransactionTranslator(
            $this->supportedCurrenciesHelper,
            $this->lineItemTranslator,
            $this->doctrineHelper,
            $this->taxProvider,
            $this->surchargeProvider,
            $this->router,
            $this->exceptionFactory
        );
    }

    public function testGetPaymentInfo()
    {
        $totalAmount = 31.39;

        $shipping = 12.35;
        $tax = 1.04;
        $subtotal = 18;
        $invoiceNumber = 567;

        $currency = 'USD';

        $paymentEntity = $this->getOrder($currency, $shipping, $subtotal, $invoiceNumber);
        $paymentEntityId = 42;

        $this->setupDoctrineHelperMock(Order::class, $paymentEntityId, $paymentEntity);
        $this->setupSurchargeMock($paymentEntity, 0.0, $shipping);
        $this->setupTaxMock($paymentEntity, $tax);

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $expectedPaymentItemsInfo = [
            new ItemInfo('foo item', $currency, 2, 6),
            new ItemInfo('foo item', $currency, 1, 6),
        ];
        $this->setupLineItemTranslatorMock($paymentEntity, $expectedPaymentItemsInfo);

        $expectedPaymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            $shipping,
            $tax,
            $subtotal,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            $invoiceNumber,
            $expectedPaymentItemsInfo
        );

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);

        $this->assertEquals($expectedPaymentInfo, $actualPaymentInfo);
    }

    public function testGetPaymentInfoWithRoundingIssues()
    {
        $totalAmount = 84.358;

        $shipping = 10.025;
        $tax = 1.013;
        $subtotal = 73.32;
        $invoiceNumber = 568;

        $currency = 'USD';

        $paymentEntity = $this->getOrder($currency, $shipping, $subtotal, $invoiceNumber);
        $paymentEntityId = 42;

        $this->setupDoctrineHelperMock(Order::class, $paymentEntityId, $paymentEntity);
        $this->setupSurchargeMock($paymentEntity, 0.0, $shipping);
        $this->setupTaxMock($paymentEntity, $tax);

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $paymentItemsInfo = [
            new ItemInfo('foo item', $currency, 13, 1.13),
            new ItemInfo('foo item', $currency, 3, 19.54),
        ];
        $this->setupLineItemTranslatorMock($paymentEntity, $paymentItemsInfo);
        $totalInfo = new ItemInfo('total', $currency, 1, $subtotal);
        $this->lineItemTranslator->expects($this->once())
            ->method('createTotalLineItem')
            ->with($currency, $subtotal)
            ->willReturn($totalInfo);

        $expectedPaymentInfo = new PaymentInfo(
            round($totalAmount, 2),
            $currency,
            round($shipping, 2),
            round($tax, 2),
            round($subtotal, 2),
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            $invoiceNumber,
            [$totalInfo]
        );

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);

        $this->assertEquals($expectedPaymentInfo, $actualPaymentInfo);
    }

    public function testGetPaymentInfoForThreeDigitsAccuracy()
    {
        $totalAmount = 84.348;

        $shipping = 10.025;
        $tax = 1.013;
        $subtotal = 73.31;
        $invoiceNumber = 568;

        $currency = 'USD';

        $paymentEntity = $this->getOrder($currency, $shipping, $subtotal, $invoiceNumber);
        $paymentEntityId = 42;

        $this->setupDoctrineHelperMock(Order::class, $paymentEntityId, $paymentEntity);
        $this->setupSurchargeMock($paymentEntity, 0.0, $shipping);
        $this->setupTaxMock($paymentEntity, $tax);

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $expectedPaymentItemsInfo = [
            new ItemInfo('foo item', $currency, 13, 1.13),
            new ItemInfo('foo item', $currency, 3, 19.54),
        ];
        $this->setupLineItemTranslatorMock($paymentEntity, $expectedPaymentItemsInfo);

        $expectedPaymentInfo = new PaymentInfo(
            round($totalAmount, 2),
            $currency,
            round($shipping, 2),
            round($tax, 2),
            round($subtotal, 2),
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            $invoiceNumber,
            $expectedPaymentItemsInfo
        );

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);

        $this->assertEquals($expectedPaymentInfo, $actualPaymentInfo);
    }

    public function testGetPaymentInfoWithDiscountAmount()
    {
        $totalAmount = 30.39;

        $shipping = 12.35;
        $discountAmount = -1;
        $tax = 1.04;
        $subtotal = 18;
        $invoiceNumber = 567;

        $currency = 'USD';

        $paymentEntity = $this->getOrder($currency, $shipping, $subtotal, $invoiceNumber);
        $paymentEntityId = 42;

        $this->setupDoctrineHelperMock(Order::class, $paymentEntityId, $paymentEntity);
        $this->setupSurchargeMock($paymentEntity, $discountAmount, $shipping);
        $this->setupTaxMock($paymentEntity, $tax);

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $expectedPaymentItemsInfo = [
            new ItemInfo('foo item', $currency, 2, 6),
            new ItemInfo('foo item', $currency, 1, 6),
            new ItemInfo('discount', $currency, 1, -1),
        ];
        $this->setupLineItemTranslatorMock($paymentEntity, $expectedPaymentItemsInfo);

        $expectedSubtotal = $subtotal + $discountAmount;
        $expectedPaymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            $shipping,
            $tax,
            $expectedSubtotal,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            $invoiceNumber,
            $expectedPaymentItemsInfo
        );

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);

        $this->assertEquals($expectedPaymentInfo, $actualPaymentInfo);
    }

    public function testGetPaymentInfoWillGenerateInvoiceNumberIfEntityIsNotAnOrder()
    {
        $totalAmount = 25.39;
        $currency = 'USD';

        $paymentEntity = new QuxPaymentEntityStub();

        $paymentEntityId = 42;

        $this->setupSurchargeMock($paymentEntity);
        $this->setupDoctrineHelperMock(QuxPaymentEntityStub::class, $paymentEntityId, $paymentEntity);
        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);

        $this->assertNotEmpty($actualPaymentInfo->getInvoiceNumber());
    }

    public function testGetPaymentInfoWillWorkCorrectlyEvenIfPaymentEntityDoesNotSupportLineItems()
    {
        $totalAmount = 25.39;
        $currency = 'USD';
        $shipping = 12.35;
        $tax = 1.04;
        $subtotal = 12.0;

        $paymentEntity = new QuxPaymentEntityStub();
        $paymentEntity->testSubtotal = $subtotal;
        $paymentEntity->testShipping = $shipping;

        $paymentEntityId = 42;

        $this->setupSurchargeMock($paymentEntity, 0, $shipping);
        $this->setupTaxMock($paymentEntity, $tax);
        $this->setupDoctrineHelperMock(QuxPaymentEntityStub::class, $paymentEntityId, $paymentEntity);

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $totalInfo = new ItemInfo('total', $currency, 1, $subtotal);
        $this->lineItemTranslator->expects($this->once())
            ->method('createTotalLineItem')
            ->with($currency, $subtotal)
            ->willReturn($totalInfo);

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);

        $expectedPaymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            $shipping,
            $tax,
            $subtotal,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            $actualPaymentInfo->getInvoiceNumber(),
            [$totalInfo]
        );

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

        $this->setupSurchargeMock($paymentEntity, 0, $shipping);
        $this->setupTaxMock($paymentEntity, $tax);
        $this->setupDoctrineHelperMock(BarPaymentEntityStub::class, $paymentEntityId, $paymentEntity);

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $actualPaymentInfo = $this->translator->getPaymentInfo($paymentTransaction);
        $expectedPaymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            $shipping,
            $tax,
            0,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            $actualPaymentInfo->getInvoiceNumber(),
            []
        );

        $this->assertEquals($expectedPaymentInfo, $actualPaymentInfo);
    }

    public function testGetPaymentInfoWillThrowAnExceptionIfUnsupportedCurrencyUsed()
    {
        $totalAmount = 25.39;
        $currency = 'Unknown Currency';
        $shipping = 12.35;
        $subtotal = 12;
        $invoiceNumber = 567;

        $paymentEntity = $this->getOrder($currency, $shipping, $subtotal, $invoiceNumber);
        $paymentEntityId = 42;

        $this->setupSurchargeMock($paymentEntity, 0, $shipping);

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $this->taxProvider->expects($this->never())
            ->method('getTax');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntity');

        $this->lineItemTranslator->expects($this->never())
            ->method('getPaymentItems');

        $expectedMessage = sprintf(
            'Currency "%s" is not supported. Only next currencies are supported: "%s"',
            $currency,
            implode($this->supportedCurrenciesHelper->getSupportedCurrencyCodes())
        );
        $unsupportedCurrencyException = new UnsupportedCurrencyException($expectedMessage);
        $this->exceptionFactory->expects($this->once())
            ->method('createUnsupportedCurrencyException')
            ->willReturn($unsupportedCurrencyException);

        $this->expectException(UnsupportedCurrencyException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->translator->getPaymentInfo($paymentTransaction);
    }

    public function testGetPaymentInfoWillThrowAnExceptionIfAmountContainsDecimalButCurrencyDoesNotAllowDecimals()
    {
        $totalAmount = 25.39;
        $currency = 'JPY';
        $shipping = 12.35;
        $subtotal = 12;

        $paymentEntity = $this->getOrder($currency, $shipping, $subtotal, 5);
        $paymentEntityId = 42;

        $this->setupSurchargeMock($paymentEntity);

        $paymentTransaction = $this->getPaymentTransaction($currency, $totalAmount, $paymentEntity, $paymentEntityId);

        $this->taxProvider->expects($this->never())
            ->method('getTax');

        $this->doctrineHelper->expects($this->never())
            ->method('getEntity');

        $this->lineItemTranslator->expects($this->never())
            ->method('getPaymentItems');

        $expectedMessage = sprintf('Decimal amount "%s" is not supported for currency "%s"', $totalAmount, $currency);
        $unsupportedValueException = new UnsupportedValueException($expectedMessage);
        $this->exceptionFactory->expects($this->once())
            ->method('createUnsupportedValueException')
            ->willReturn($unsupportedValueException);

        $this->expectException(UnsupportedValueException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->translator->getPaymentInfo($paymentTransaction);
    }

    private function setupSurchargeMock(
        object $paymentEntity,
        float $surchargeDiscount = 0.0,
        float $surchargeShipping = 0.0
    ): void {
        $this->surchargeProvider->expects($this->any())
            ->method('getSurcharges')
            ->with($paymentEntity)
            ->willReturn($this->getSurcharge($surchargeDiscount, $surchargeShipping));
    }

    private function setupTaxMock(object $paymentEntity, float $tax): void
    {
        $this->taxProvider->expects($this->any())
            ->method('getTax')
            ->with($paymentEntity)
            ->willReturn($tax);
    }

    private function setupDoctrineHelperMock(string $class, int $id, object $entity): void
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntity')
            ->with($class, $id)
            ->willReturn($entity);
    }

    private function setupLineItemTranslatorMock(object $paymentEntity, array $paymentItems): void
    {
        $this->lineItemTranslator->expects($this->once())
            ->method('getPaymentItems')
            ->with($paymentEntity)
            ->willReturn($paymentItems);
    }

    public function testGetRedirectRoutes()
    {
        $accessIdentifier = 'test_1';
        $expectedSuccessRoute = "http://example.com/payment/callback/return/$accessIdentifier";
        $expectedFailedRoute = "http://example.com/payment/error/return/$accessIdentifier";

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnMap([
                [
                    'oro_payment_callback_return',
                    [
                        'accessIdentifier' => $accessIdentifier,
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                    $expectedSuccessRoute
                ],
                [
                    'oro_payment_callback_error',
                    [
                        'accessIdentifier' => $accessIdentifier,
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                    $expectedFailedRoute
                ]
            ]);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setAccessIdentifier($accessIdentifier);

        $actualRoutes = $this->translator->getRedirectRoutes($paymentTransaction);

        $this->assertInstanceOf(RedirectRoutesInfo::class, $actualRoutes);
        $this->assertEquals($actualRoutes->getSuccessRoute(), $expectedSuccessRoute);
        $this->assertEquals($actualRoutes->getFailedRoute(), $expectedFailedRoute);
    }

    private function getSurcharge(float $discount, float $shipping): Surcharge
    {
        $surcharge = new Surcharge();
        $surcharge->setDiscountAmount($discount);
        $surcharge->setShippingAmount($shipping);

        return $surcharge;
    }

    private function getOrder(string $currency, float $shipping, float $subtotal, string $identifier): Order
    {
        $order = new Order();
        $order->setEstimatedShippingCostAmount($shipping);
        $order->setCurrency($currency);
        $order->setSubtotal($subtotal);
        $order->setIdentifier($identifier);

        return $order;
    }

    private function getPaymentTransaction(
        string $currency,
        float $totalAmount,
        object $paymentEntity,
        int $paymentEntityId
    ): PaymentTransaction {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setCurrency($currency);
        $paymentTransaction->setAmount($totalAmount);
        $paymentTransaction->setEntityClass(get_class($paymentEntity));
        $paymentTransaction->setEntityIdentifier($paymentEntityId);

        return $paymentTransaction;
    }
}
