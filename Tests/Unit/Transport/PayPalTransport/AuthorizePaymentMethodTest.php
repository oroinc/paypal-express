<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport\PayPalTransport;

use Oro\Bundle\PayPalExpressBundle\Transport\Exception\Context;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalExpressTransport;
use PayPal\Api\Authorization;

class AuthorizePaymentMethodTest extends AbstractTransportTestCase
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

    public function testCanAuthorizePayment()
    {
        $this->expectTranslatorGetApiContext();

        $order = $this->createOrder($this->expectedOrderId);

        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($this->expectedOrderId, $this->apiContext)
            ->willReturn($order);

        $authorization = new Authorization();
        $this->translator
            ->expects($this->once())
            ->method('getAuthorization')
            ->with($this->paymentInfo)
            ->willReturn($authorization);

        $responseAuthorization = $this->createAuthorization(PayPalExpressTransport::ORDER_PAYMENT_AUTHORIZED_STATUS);

        $this->client->expects($this->once())
            ->method('authorizeOrder')
            ->with($order, $authorization, $this->apiContext)
            ->willReturn($responseAuthorization);

        $this->transport->authorizePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testThrowExceptionIfOrderIdIsNotDefined()
    {
        $this->paymentInfo->setOrderId(null);

        $this->expectTransportException(
            'Cannot authorize payment. Order Id is required.',
            (new Context())->setPaymentInfo($this->paymentInfo),
            null
        );

        $this->transport->authorizePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testThrowExceptionAuthorizationFailed()
    {
        $clientException = new \Exception();

        $this->expectTranslatorGetApiContext();

        $order = $this->createOrder($this->expectedOrderId);

        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($this->expectedOrderId, $this->apiContext)
            ->willReturn($order);

        $authorization = new Authorization();
        $this->translator
            ->expects($this->once())
            ->method('getAuthorization')
            ->with($this->paymentInfo)
            ->willReturn($authorization);

        $this->client->expects($this->once())
            ->method('authorizeOrder')
            ->with($order, $authorization, $this->apiContext)
            ->willThrowException($clientException);

        $this->expectTransportException(
            'Payment order authorization failed.',
            (new Context())->setPaymentInfo($this->paymentInfo),
            $clientException
        );

        $this->transport->authorizePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testThrowExceptionWhenAuthorizationStateNotExpected()
    {
        $expectedAuthorizationState = 'failed';
        $expectedAuthorizationReasonCode = 'AUTHORIZATION';
        $expectedAuthorizationValidUntil = '2018-01-01';

        $this->expectTranslatorGetApiContext();

        $order = $this->createOrder($this->expectedOrderId);

        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($this->expectedOrderId, $this->apiContext)
            ->willReturn($order);

        $requestAuthorization = new Authorization();
        $this->translator
            ->expects($this->once())
            ->method('getAuthorization')
            ->with($this->paymentInfo)
            ->willReturn($requestAuthorization);

        $responseAuthorization = $this->createAuthorization(
            $expectedAuthorizationState,
            $expectedAuthorizationReasonCode,
            $expectedAuthorizationValidUntil
        );

        $this->client->expects($this->once())
            ->method('authorizeOrder')
            ->with($order, $requestAuthorization, $this->apiContext)
            ->willReturn($responseAuthorization);

        $this->expectTransportException(
            'Unexpected state of payment authorization.',
            (new Context())->setPaymentInfo($this->paymentInfo)->setAuthorization($responseAuthorization),
            null
        );

        $this->transport->authorizePayment($this->paymentInfo, $this->apiContextInfo);
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
