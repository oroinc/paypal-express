<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PayPalExpressBundle\EventListener\PayPalExpressRedirectListener;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CompleteVirtualAction;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class PayPalExpressRedirectListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentMethodProviderInterface|MockObject
     */
    private $paymentMethodProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PayPalExpressRedirectListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new PayPalExpressRedirectListener($this->paymentMethodProvider);
        $this->listener->setLogger($this->logger);
    }

    public function testOnErrorWithEmptyTransaction()
    {
        $event = new CallbackErrorEvent();

        $this->paymentMethodProvider->expects($this->never())
            ->method($this->anything());

        $this->listener->onError($event);

        $this->assertNull($event->getPaymentTransaction());
    }

    public function testOnErrorWithUnknownPaymentMethod()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful(true);
        $paymentTransaction->setPaymentMethod('test');
        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('test')
            ->willReturn(false);

        $this->listener->onError($event);

        $this->assertTrue($event->getPaymentTransaction()->isActive());
        $this->assertTrue($event->getPaymentTransaction()->isSuccessful());
    }

    public function testOnErrorWithInactivePaymentTransaction()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setActive(false);
        $paymentTransaction->setSuccessful(true);
        $paymentTransaction->setPaymentMethod('test');
        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onError($event);

        $this->assertFalse($event->getPaymentTransaction()->isActive());
        $this->assertTrue($event->getPaymentTransaction()->isSuccessful());
    }

    public function testOnError()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful(true);
        $paymentTransaction->setPaymentMethod('test');
        $event = new CallbackErrorEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('test')
            ->willReturn(true);

        $this->listener->onError($event);

        $this->assertFalse($event->getPaymentTransaction()->isActive());
        $this->assertFalse($event->getPaymentTransaction()->isSuccessful());
    }

    public function testOnReturnWithEmptyTransaction()
    {
        $event = new CallbackReturnEvent();

        $this->paymentMethodProvider->expects($this->never())
            ->method($this->anything());

        $this->listener->onReturn($event);

        $this->assertNull($event->getPaymentTransaction());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnReturnWithUnknownPaymentMethod()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful(true);
        $paymentTransaction->setPaymentMethod('test');
        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('test')
            ->willReturn(false);

        $this->listener->onReturn($event);

        $this->assertTrue($event->getPaymentTransaction()->isActive());
        $this->assertTrue($event->getPaymentTransaction()->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnReturnWithInactivePaymentTransaction()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setActive(false);
        $paymentTransaction->setSuccessful(true);
        $paymentTransaction->setPaymentMethod('test');
        $event = new CallbackReturnEvent();
        $event->setPaymentTransaction($paymentTransaction);

        $this->listener->onReturn($event);

        $this->assertFalse($event->getPaymentTransaction()->isActive());
        $this->assertTrue($event->getPaymentTransaction()->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnReturnWithoutRequiredData()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful(true);
        $paymentTransaction->setPaymentMethod('test');

        $eventData = ['paymentId' => 1];
        $event = new CallbackReturnEvent($eventData);
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('test')
            ->willReturn(true);

        $this->listener->onReturn($event);

        $this->assertTrue($event->getPaymentTransaction()->isActive());
        $this->assertTrue($event->getPaymentTransaction()->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnReturnException()
    {
        $reference = 1;
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful(true);
        $paymentTransaction->setPaymentMethod('test');
        $paymentTransaction->setReference($reference);

        $eventData = [
            'paymentId' => $reference,
            'PayerID' => 2,
            'token' => 3
        ];
        $event = new CallbackReturnEvent($eventData);
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('test')
            ->willReturn(true);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects($this->once())
            ->method('execute')
            ->with(CompleteVirtualAction::NAME, $paymentTransaction)
            ->willThrowException(new \InvalidArgumentException());
        $this->paymentMethodProvider->expects($this->once())
            ->method('getPaymentMethod')
            ->with('test')
            ->willReturn($paymentMethod);

        $this->logger->expects($this->once())
            ->method('error');

        $this->listener->onReturn($event);

        $this->assertTrue($event->getPaymentTransaction()->isActive());
        $this->assertTrue($event->getPaymentTransaction()->isSuccessful());
        $this->assertEquals(Response::HTTP_FORBIDDEN, $event->getResponse()->getStatusCode());
    }

    public function testOnReturn()
    {
        $reference = 1;
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful(true);
        $paymentTransaction->setPaymentMethod('test');
        $paymentTransaction->setReference($reference);

        $eventData = [
            'paymentId' => $reference,
            'PayerID' => 2,
            'token' => 3
        ];
        $event = new CallbackReturnEvent($eventData);
        $event->setPaymentTransaction($paymentTransaction);

        $this->paymentMethodProvider->expects($this->once())
            ->method('hasPaymentMethod')
            ->with('test')
            ->willReturn(true);

        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->expects($this->once())
            ->method('execute')
            ->with(CompleteVirtualAction::NAME, $paymentTransaction);
        $this->paymentMethodProvider->expects($this->once())
            ->method('getPaymentMethod')
            ->with('test')
            ->willReturn($paymentMethod);

        $this->logger->expects($this->never())
            ->method($this->anything());

        $this->listener->onReturn($event);

        $this->assertTrue($event->getPaymentTransaction()->isActive());
        $this->assertTrue($event->getPaymentTransaction()->isSuccessful());
        $this->assertEquals(Response::HTTP_OK, $event->getResponse()->getStatusCode());
    }
}
