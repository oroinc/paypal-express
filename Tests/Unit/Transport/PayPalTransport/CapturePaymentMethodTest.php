<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport\PayPalTransport;

use Oro\Bundle\PayPalExpressBundle\Transport\Exception\Context;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalExpressTransport;
use PayPal\Api\Authorization;
use PayPal\Api\Capture;

class CapturePaymentMethodTest extends AbstractTransportTestCase
{
    /**
     * @var string
     */
    protected $expectedPaymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

    /**
     * @var string
     */
    protected $expectedOrderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

    protected function setUp()
    {
        parent::setUp();
        $this->paymentInfo = $this->createPaymentInfo($this->expectedPaymentId, $this->expectedOrderId);
    }

    public function testCanCapturePayment()
    {
        $this->expectTranslatorGetApiContext();

        $order = $this->createOrder($this->expectedOrderId);

        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($this->expectedOrderId, $this->apiContext)
            ->willReturn($order);

        $requestCapture = $this->createCapture();
        $this->translator
            ->expects($this->once())
            ->method('getCapturedDetails')
            ->with($this->paymentInfo)
            ->willReturn($requestCapture);

        $responseCapture = $this->createCapture(PayPalExpressTransport::ORDER_PAYMENT_CAPTURED_STATUS);

        $this->client->expects($this->once())
            ->method('captureOrder')
            ->with($order, $requestCapture, $this->apiContext)
            ->willReturn($responseCapture);

        $this->transport->capturePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testThrowExceptionWhenOrderIdIsNotDefined()
    {
        $this->paymentInfo->setOrderId(null);

        $this->expectTransportException(
            'Cannot capture payment. Order Id is required.',
            (new Context())->setPaymentInfo($this->paymentInfo),
            null
        );

        $this->transport->capturePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testThrowExceptionWhenClientGetOrderByIdFailed()
    {
        $clientException = new \Exception();

        $this->expectTranslatorGetApiContext();

        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($this->expectedOrderId, $this->apiContext)
            ->willThrowException($clientException);

        $this->expectTransportException(
            'Payment capture failed.',
            (new Context())->setPaymentInfo($this->paymentInfo),
            $clientException
        );

        $this->transport->capturePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testThrowExceptionWhenClientCaptureOrderFailed()
    {
        $clientException = new \Exception();

        $this->expectTranslatorGetApiContext();

        $order = $this->createOrder($this->expectedOrderId);

        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($this->expectedOrderId, $this->apiContext)
            ->willReturn($order);

        $requestCapture = $this->createCapture();
        $this->translator
            ->expects($this->once())
            ->method('getCapturedDetails')
            ->with($this->paymentInfo)
            ->willReturn($requestCapture);

        $this->client->expects($this->once())
            ->method('captureOrder')
            ->with($order, $requestCapture, $this->apiContext)
            ->willThrowException($clientException);

        $this->expectTransportException(
            'Payment capture failed.',
            (new Context())->setPaymentInfo($this->paymentInfo),
            $clientException
        );

        $this->transport->capturePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testThrowExceptionWhenCaptureStateNotExpected()
    {
        $expectedCaptureState = 'failed';
        $expectedCaptureParentPayment = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $this->expectTranslatorGetApiContext();

        $order = $this->createOrder($this->expectedOrderId);

        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($this->expectedOrderId, $this->apiContext)
            ->willReturn($order);

        $requestCapture = $this->createCapture();
        $this->translator
            ->expects($this->once())
            ->method('getCapturedDetails')
            ->with($this->paymentInfo)
            ->willReturn($requestCapture);

        $responseCapture = $this->createCapture($expectedCaptureState, $expectedCaptureParentPayment);

        $this->client->expects($this->once())
            ->method('captureOrder')
            ->with($order, $requestCapture, $this->apiContext)
            ->willReturn($responseCapture);

        $this->expectTransportException(
            'Unexpected payment state after capture.',
            (new Context())->setPaymentInfo($this->paymentInfo)->setCapture($responseCapture),
            null
        );

        $this->transport->capturePayment($this->paymentInfo, $this->apiContextInfo);
    }

    /**
     * @param string|null $state
     * @param string|null $parentPayment
     * @return Capture
     */
    protected function createCapture($state = null, $parentPayment = null)
    {
        $capture = new Capture();
        $capture->setState($state);
        $capture->setParentPayment($parentPayment);

        return $capture;
    }

    /**
     * @param string|null $state
     * @param string|null $reason
     * @param string|null $validUntil
     *
     * @return Authorization
     */
    protected function createAuthorization($state = null, $reason = null, $validUntil = null)
    {
        $authorization = new Authorization();
        $authorization->setState($state);
        $authorization->setReasonCode($reason);
        $authorization->setValidUntil($validUntil);

        return $authorization;
    }
}
