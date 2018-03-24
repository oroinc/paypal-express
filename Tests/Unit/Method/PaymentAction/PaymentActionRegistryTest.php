<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\LogicException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\AuthorizeAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionRegistry;
use Psr\Log\LoggerInterface;

class PaymentActionRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentActionRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PaymentActionInterface
     */
    protected $paymentAction;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExceptionFactory
     */
    protected $exceptionFactory;

    protected function setUp()
    {
        $this->exceptionFactory = $this->createMock(ExceptionFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->paymentAction = $this->createMockAction();
        $this->registry = new PaymentActionRegistry($this->exceptionFactory);
    }

    public function testRegisterAction()
    {
        $this->registry->registerAction($this->paymentAction);

        $actualPaymentAction = $this->registry->getPaymentAction($this->paymentAction->getName());
        $this->assertSame($this->paymentAction, $actualPaymentAction);
    }

    /**
     * @param string $actionName
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockAction($actionName = 'test')
    {
        $result = $this->createMock(PaymentActionInterface::class);
        $result->expects($this->any())
            ->method('getName')
            ->willReturn($actionName);
        return $result;
    }

    public function testRegisterActionShouldNotAllowRegisterTwoActionsWithTheSameName()
    {
        $this->paymentAction = $this->createMockAction();

        $exceptionMessage = 'Payment Action with the same name is already registered';
        $this->exceptionFactory->expects($this->once())
            ->method('createLogicException')
            ->with($exceptionMessage)
            ->willReturn(new LogicException($exceptionMessage));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->registry->registerAction($this->paymentAction);
        $this->registry->registerAction($this->paymentAction);
    }

    public function testIsActionSupported()
    {
        $this->paymentAction = $this->createMockAction();
        $this->registry->registerAction($this->paymentAction);

        $this->assertTrue($this->registry->isActionSupported($this->paymentAction->getName()));
        $this->assertFalse($this->registry->isActionSupported('unsupported action'));
    }

    public function testGetPaymentActionShouldThrowAnExceptionIfActionDoesNotSupported()
    {
        $expectedMessage = 'Payment Action "unsupported action" is not supported';
        $this->exceptionFactory->expects($this->once())
            ->method('createRuntimeException')
            ->with($expectedMessage)
            ->willReturn(new RuntimeException($expectedMessage));
        $this->expectExceptionMessage($expectedMessage);
        $this->registry->getPaymentAction('unsupported action');
    }
}
