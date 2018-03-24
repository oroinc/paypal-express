<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionExecutor;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalMethod;
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

    protected function setUp()
    {
        $this->config = $this->createMock(PayPalExpressConfigInterface::class);
        $this->actionExecutor = $this->createMock(PaymentActionExecutor::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->payPalMethod = new PayPalMethod($this->config, $this->actionExecutor);
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
}
