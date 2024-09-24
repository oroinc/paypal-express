<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\LogicException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionRegistry;

class PaymentActionRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentActionRegistry */
    private $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExceptionFactory */
    private $exceptionFactory;

    #[\Override]
    protected function setUp(): void
    {
        $this->exceptionFactory = $this->createMock(ExceptionFactory::class);

        $this->registry = new PaymentActionRegistry($this->exceptionFactory, []);
    }

    private function createPaymentAction(string $actionName = 'test'): PaymentActionInterface
    {
        $result = $this->createMock(PaymentActionInterface::class);
        $result->expects($this->any())
            ->method('getName')
            ->willReturn($actionName);

        return $result;
    }

    public function testRegisterAction()
    {
        $paymentAction = $this->createPaymentAction();
        $this->registry->registerAction($paymentAction);

        $actualPaymentAction = $this->registry->getPaymentAction($paymentAction->getName());
        $this->assertSame($paymentAction, $actualPaymentAction);
    }

    public function testRegisterActionShouldNotAllowRegisterTwoActionsWithTheSameName()
    {
        $paymentAction = $this->createPaymentAction();

        $exceptionMessage = 'Payment Action with the same name is already registered';
        $this->exceptionFactory->expects($this->once())
            ->method('createLogicException')
            ->with($exceptionMessage)
            ->willReturn(new LogicException($exceptionMessage));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->registry->registerAction($paymentAction);
        $this->registry->registerAction($paymentAction);
    }

    public function testIsActionSupported()
    {
        $paymentAction = $this->createPaymentAction();
        $this->registry->registerAction($paymentAction);

        $this->assertTrue($this->registry->isActionSupported($paymentAction->getName()));
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
        $paymentAction = $this->createPaymentAction();
        $secondPaymentAction = $this->createPaymentAction('test2');
        $registry = new PaymentActionRegistry(
            $this->exceptionFactory,
            [$paymentAction, $secondPaymentAction]
        );

        $actualPaymentAction = $registry->getPaymentAction($paymentAction->getName());
        $this->assertSame($paymentAction, $actualPaymentAction);
        $secondActualPaymentAction = $registry->getPaymentAction($secondPaymentAction->getName());
        $this->assertSame($secondPaymentAction, $secondActualPaymentAction);
    }

    public function testRegisterActionsShouldNotAllowRegisterTwoActionsWithTheSameName()
    {
        $paymentAction = $this->createPaymentAction();

        $exceptionMessage = 'Payment Action with the same name is already registered';
        $this->exceptionFactory->expects($this->once())
            ->method('createLogicException')
            ->with($exceptionMessage)
            ->willReturn(new LogicException($exceptionMessage));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->registry->registerActions([$paymentAction]);
        $this->registry->registerActions([$paymentAction]);
    }
}
