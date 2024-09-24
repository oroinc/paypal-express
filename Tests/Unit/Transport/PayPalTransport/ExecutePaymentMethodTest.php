<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport\PayPalTransport;

use Oro\Bundle\PayPalExpressBundle\Transport\Exception\Context;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalExpressTransport;
use PayPal\Api\PaymentExecution;

class ExecutePaymentMethodTest extends AbstractTransportTestCase
{
    private string $expectedPaymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentInfo = $this->createPaymentInfo($this->expectedPaymentId);
    }

    public function testCanExecutePaymentAndUpdatePaymentInfo()
    {
        $expectedOrderId = '123';

        $this->expectTranslatorGetApiContext();

        $execution = new PaymentExecution();
        $this->translator
            ->expects($this->once())
            ->method('getPaymentExecution')
            ->with($this->paymentInfo)
            ->willReturn($execution);

        $order = $this->createOrder($expectedOrderId);
        $payment = $this->createPayment($this->expectedPaymentId);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($this->expectedPaymentId, $this->apiContext)
            ->willReturn($payment);

        $executedPayment = $this->createPaymentWithOrder(
            $order,
            $this->expectedPaymentId,
            PayPalExpressTransport::PAYMENT_EXECUTED_STATUS
        );

        $this->client->expects($this->once())
            ->method('executePayment')
            ->with($payment, $execution, $this->apiContext)
            ->willReturn($executedPayment);

        $this->transport->executePayment($this->paymentInfo, $this->apiContextInfo);

        $this->assertEquals($expectedOrderId, $this->paymentInfo->getOrderId());
    }

    public function testCanThrowExceptionWhenPaymentHasNoOrder()
    {
        $expectedPaymentState = 'failed';
        $expectedFailureReason = 'Payment failed because of some error';

        $this->expectTranslatorGetApiContext();

        $execution = new PaymentExecution();
        $this->translator
            ->expects($this->once())
            ->method('getPaymentExecution')
            ->with($this->paymentInfo)
            ->willReturn($execution);

        $payment = $this->createPayment($this->expectedPaymentId);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($this->expectedPaymentId, $this->apiContext)
            ->willReturn($payment);

        $executedPayment = $this->createPaymentWithOrder(
            null,
            $this->expectedPaymentId,
            $expectedPaymentState,
            $expectedFailureReason
        );

        $this->client->expects($this->once())
            ->method('executePayment')
            ->with($payment, $execution, $this->apiContext)
            ->willReturn($executedPayment);

        $this->expectTransportException(
            'Order was not created for payment after execute.',
            (new Context())->setPaymentInfo($this->paymentInfo)->setPayment($executedPayment)
        );

        $this->transport->executePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testCanThrowExceptionWhenClientGetPaymentByIdFails()
    {
        $clientException = new \Exception();

        $this->expectTranslatorGetApiContext();

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($this->expectedPaymentId, $this->apiContext)
            ->willThrowException($clientException);

        $this->expectTransportException(
            'Execute payment failed.',
            (new Context())->setPaymentInfo($this->paymentInfo),
            $clientException
        );

        $this->transport->executePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testCanThrowExceptionWhenClientExecutePaymentFails()
    {
        $clientException = new \Exception();
        $expectedPaymentState = PayPalExpressTransport::PAYMENT_CREATED_STATUS;
        $expectedFailureReason = null;

        $this->expectTranslatorGetApiContext();

        $execution = new PaymentExecution();
        $this->translator
            ->expects($this->once())
            ->method('getPaymentExecution')
            ->with($this->paymentInfo)
            ->willReturn($execution);

        $payment = $this->createPayment($this->expectedPaymentId, $expectedPaymentState, $expectedFailureReason);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($this->expectedPaymentId, $this->apiContext)
            ->willReturn($payment);

        $this->client->expects($this->once())
            ->method('executePayment')
            ->with($payment, $execution, $this->apiContext)
            ->willThrowException($clientException);

        $this->expectTransportException(
            'Execute payment failed.',
            (new Context())->setPaymentInfo($this->paymentInfo)->setPayment($payment),
            $clientException
        );

        $this->transport->executePayment($this->paymentInfo, $this->apiContextInfo);
    }
}
