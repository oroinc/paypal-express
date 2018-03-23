<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\LogicException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\AuthorizeAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionRegistry;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalTransportFacadeInterface;

class PaymentActionRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentActionRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExceptionFactory
     */
    protected $exceptionFactory;

    protected function setUp()
    {
        $this->exceptionFactory = $this->createMock(ExceptionFactory::class);
        $this->registry = new PaymentActionRegistry($this->exceptionFactory);
    }

    public function testRegisterAction()
    {
        $facade = $this->createMock(PayPalTransportFacadeInterface::class);
        $paymentAction = new AuthorizeAction($facade);

        $this->registry->registerAction($paymentAction);

        $actualPaymentAction = $this->registry->getPaymentAction($paymentAction->getName());
        $this->assertSame($paymentAction, $actualPaymentAction);
    }

    public function testRegisterActionShouldNotAllowRegisterTwoActionsWithTheSameName()
    {
        $facade = $this->createMock(PayPalTransportFacadeInterface::class);
        $paymentAction = new AuthorizeAction($facade);

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
        $facade = $this->createMock(PayPalTransportFacadeInterface::class);
        $paymentAction = new AuthorizeAction($facade);
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
}
