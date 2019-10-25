<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\LogicException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionRegistry;
use Psr\Log\LoggerInterface;

class PaymentActionRegistryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentActionRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PaymentActionInterface
     */
    protected $paymentAction;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExceptionFactory
     */
    protected $exceptionFactory;

    protected function setUp()
    {
        $this->exceptionFactory = $this->createMock(ExceptionFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->registry = new PaymentActionRegistry($this->exceptionFactory, []);
    }

    public function testRegisterAction()
    {
        $this->paymentAction = $this->createMockAction();
        $this->registry->registerAction($this->paymentAction);

        $actualPaymentAction = $this->registry->getPaymentAction($this->paymentAction->getName());
        $this->assertSame($this->paymentAction, $actualPaymentAction);
    }

    /**
     * @param string $actionName
     * @return \PHPUnit\Framework\MockObject\MockObject
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

    public function testRegisterActions()
    {
        $this->paymentAction = $this->createMockAction();
        $secondPaymentAction = $this->createMockAction('test2');
        $registry = new PaymentActionRegistry(
            $this->exceptionFactory,
            [$this->paymentAction, $secondPaymentAction]
        );

        $actualPaymentAction = $registry->getPaymentAction($this->paymentAction->getName());
        $this->assertSame($this->paymentAction, $actualPaymentAction);
        $secondActualPaymentAction = $registry->getPaymentAction($secondPaymentAction->getName());
        $this->assertSame($secondPaymentAction, $secondActualPaymentAction);
    }

    public function testRegisterActionsShouldNotAllowRegisterTwoActionsWithTheSameName()
    {
        $this->paymentAction = $this->createMockAction();

        $exceptionMessage = 'Payment Action with the same name is already registered';
        $this->exceptionFactory->expects($this->once())
            ->method('createLogicException')
            ->with($exceptionMessage)
            ->willReturn(new LogicException($exceptionMessage));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->registry->registerActions([$this->paymentAction]);
        $this->registry->registerActions([$this->paymentAction]);
    }
}
