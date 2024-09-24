<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionExecutor;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalExpressMethod;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;

class PayPalExpressMethodTest extends \PHPUnit\Framework\TestCase
{
    /** @var PayPalExpressMethod */
    private $payPalExpressMethod;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PayPalExpressConfigInterface */
    private $config;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PaymentActionExecutor */
    private $actionExecutor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SupportedCurrenciesHelper */
    private $supportedCurrenciesHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->config = $this->createMock(PayPalExpressConfigInterface::class);
        $this->actionExecutor = $this->createMock(PaymentActionExecutor::class);
        $this->supportedCurrenciesHelper = $this->createMock(SupportedCurrenciesHelper::class);

        $this->payPalExpressMethod = new PayPalExpressMethod(
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

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects($this->once())
            ->method('getCurrency')
            ->willReturn($supportedCurrency);

        $this->supportedCurrenciesHelper->expects($this->once())
            ->method('isSupportedCurrency')
            ->with($supportedCurrency)
            ->willReturn(false);

        $this->assertFalse($this->payPalExpressMethod->isApplicable($context));
    }

    /**
     * @dataProvider isApplicableByAmountDataProvider
     */
    public function testIsApplicableByAmount(float $amount, bool $expectedIsApplicable): void
    {
        $supportedCurrency = 'USD';

        $context = $this->createMock(PaymentContext::class);
        $context->expects($this->once())
            ->method('getCurrency')
            ->willReturn($supportedCurrency);
        $context->expects($this->once())
            ->method('getTotal')
            ->willReturn($amount);

        $this->supportedCurrenciesHelper->expects($this->once())
            ->method('isSupportedCurrency')
            ->with($supportedCurrency)
            ->willReturn(true);

        $this->assertEquals($expectedIsApplicable, $this->payPalExpressMethod->isApplicable($context));
    }

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
}
