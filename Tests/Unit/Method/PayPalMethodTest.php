<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionExecutor;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalMethod;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;
use Psr\Log\LoggerInterface;

class PayPalMethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PayPalMethod
     */
    protected $payPalMethod;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PayPalExpressConfigInterface
     */
    protected $config;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PaymentActionExecutor
     */
    protected $actionExecutor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SupportedCurrenciesHelper
     */
    protected $supportedCurrenciesHelper;

    protected function setUp()
    {
        $this->config = $this->createMock(PayPalExpressConfigInterface::class);
        $this->actionExecutor = $this->createMock(PaymentActionExecutor::class);
        $this->supportedCurrenciesHelper = $this->createMock(SupportedCurrenciesHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->payPalMethod = new PayPalMethod(
            $this->config,
            $this->actionExecutor,
            $this->supportedCurrenciesHelper
        );
    }

    public function testCanExecuteAction()
    {
        $action = 'execute';
        $paymentTransaction = new PaymentTransaction();
        $expectedResult = ['successful' => false, 'message' => 'Payment was executed.'];

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with($action, $paymentTransaction, $this->config)
            ->willReturn($expectedResult);


        $actualResult = $this->payPalMethod->execute($action, $paymentTransaction);
        $this->assertEquals($expectedResult, $actualResult);
    }

    public function testIsApplicableReturnTrueWithSupportedCurrency()
    {
        $supportedCurrency = 'USD';

        $context = $this->createPaymentContext($supportedCurrency);
        $this->expectCurrencyIsSupported($supportedCurrency, true);

        $this->assertTrue($this->payPalMethod->isApplicable($context));
    }

    public function testIsApplicableReturnFalseWithNotSupportedCurrency()
    {
        $supportedCurrency = 'UAH';

        $context = $this->createPaymentContext($supportedCurrency);
        $this->expectCurrencyIsSupported($supportedCurrency, false);

        $this->assertFalse($this->payPalMethod->isApplicable($context));
    }

    /**
     * @param string $currency
     * @return \PHPUnit_Framework_MockObject_MockObject|PaymentContextInterface
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
