<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionExecutor;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalExpressMethod;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;
use Psr\Log\LoggerInterface;

class PayPalExpressMethodTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PayPalExpressMethod
     */
    protected $payPalExpressMethod;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PayPalExpressConfigInterface
     */
    protected $config;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PaymentActionExecutor
     */
    protected $actionExecutor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SupportedCurrenciesHelper
     */
    protected $supportedCurrenciesHelper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected $logger;

    protected function setUp(): void
    {
        $this->config                    = $this->createMock(PayPalExpressConfigInterface::class);
        $this->actionExecutor            = $this->createMock(PaymentActionExecutor::class);
        $this->supportedCurrenciesHelper = $this->createMock(SupportedCurrenciesHelper::class);
        $this->logger                    = $this->createMock(LoggerInterface::class);
        $this->payPalExpressMethod       = new PayPalExpressMethod(
            $this->config,
            $this->actionExecutor,
            $this->supportedCurrenciesHelper
        );
    }

    public function testCanExecuteAction()
    {
        $action = 'execute';
        $paymentTransaction = new PaymentTransaction();
        $expectedResult = ['successful' => true, 'message' => 'Payment was executed.'];

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with($action, $paymentTransaction, $this->config)
            ->willReturn($expectedResult);


        $actualResult = $this->payPalExpressMethod->execute($action, $paymentTransaction);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testIsApplicableReturnFalseWithNotSupportedCurrency()
    {
        $supportedCurrency = 'UAH';

        $context = $this->createPaymentContext($supportedCurrency);
        $this->expectCurrencyIsSupported($supportedCurrency, false);

        $this->assertFalse($this->payPalExpressMethod->isApplicable($context));
    }

    /**
     * @param float $amount
     * @param bool $expectedIsApplicable
     * @dataProvider isApplicableByAmountDataProvider
     */
    public function testIsApplicableByAmount(float $amount, bool $expectedIsApplicable): void
    {
        $supportedCurrency = 'USD';

        /** @var PaymentContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(PaymentContext::class);
        $context->expects($this->once())
            ->method('getCurrency')
            ->willReturn($supportedCurrency);
        $this->expectCurrencyIsSupported($supportedCurrency, true);
        $context->expects($this->once())
            ->method('getTotal')
            ->willReturn($amount);

        $this->assertEquals($expectedIsApplicable, $this->payPalExpressMethod->isApplicable($context));
    }

    /**
     * @return array
     */
    public function isApplicableByAmountDataProvider(): array
    {
        return [
            'not applicable if order total is zero' => [
                'amount' => 0.0,
                'expectedIsApplicable' => false
            ],
            'applicable if order total is greater than zero' => [
                'amount' => 0.1,
                'expectedIsApplicable' => true
            ]
        ];
    }

    /**
     * @param string $currency
     * @return \PHPUnit\Framework\MockObject\MockObject|PaymentContextInterface
     */
    protected function createPaymentContext($currency)
    {
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);
        return $context;
    }

    /**
     * @param string $currency
     * @param bool $isSupported
     */
    protected function expectCurrencyIsSupported($currency, $isSupported)
    {
        $this->supportedCurrenciesHelper->expects($this->once())
            ->method('isSupportedCurrency')
            ->with($currency)
            ->willReturn($isSupported);
    }
}
