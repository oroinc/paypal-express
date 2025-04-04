<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport\PayPalTransport;

use Oro\Bundle\PayPalExpressBundle\Transport\Exception\Context;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalExpressTransport;
use PayPal\Api\Authorization;

class AuthorizePaymentMethodTest extends AbstractTransportTestCase
{
    private string $expectedPaymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
    private string $expectedOrderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentInfo = $this->createPaymentInfo($this->expectedPaymentId, $this->expectedOrderId);
    }

    public function testCanAuthorizePayment(): void
    {
        $this->expectTranslatorGetApiContext();

        $order = $this->createOrder($this->expectedOrderId);

        $this->client->expects(self::once())
            ->method('getOrderById')
            ->with($this->expectedOrderId, $this->apiContext)
            ->willReturn($order);

        $authorization = new Authorization();
        $this->translator->expects(self::once())
            ->method('getAuthorization')
            ->with($this->paymentInfo)
            ->willReturn($authorization);

        $responseAuthorization = $this->createAuthorization(PayPalExpressTransport::ORDER_PAYMENT_AUTHORIZED_STATUS);

        $this->client->expects(self::once())
            ->method('authorizeOrder')
            ->with($order, $authorization, $this->apiContext)
            ->willReturn($responseAuthorization);

        $this->transport->authorizePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testThrowExceptionIfOrderIdIsNotDefined(): void
    {
        $this->paymentInfo->setOrderId(null);

        $this->expectTransportException(
            'Cannot authorize payment. Order Id is required.',
            (new Context())->setPaymentInfo($this->paymentInfo)
        );

        $this->transport->authorizePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testThrowExceptionAuthorizationFailed(): void
    {
        $clientException = new \Exception();

        $this->expectTranslatorGetApiContext();

        $order = $this->createOrder($this->expectedOrderId);

        $this->client->expects(self::once())
            ->method('getOrderById')
            ->with($this->expectedOrderId, $this->apiContext)
            ->willReturn($order);

        $authorization = new Authorization();
        $this->translator->expects(self::once())
            ->method('getAuthorization')
            ->with($this->paymentInfo)
            ->willReturn($authorization);

        $this->client->expects(self::once())
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

    public function testThrowExceptionWhenAuthorizationStateNotExpected(): void
    {
        $expectedAuthorizationState = 'failed';
        $expectedAuthorizationReasonCode = 'AUTHORIZATION';
        $expectedAuthorizationValidUntil = '2018-01-01';

        $this->expectTranslatorGetApiContext();

        $order = $this->createOrder($this->expectedOrderId);

        $this->client->expects(self::once())
            ->method('getOrderById')
            ->with($this->expectedOrderId, $this->apiContext)
            ->willReturn($order);

        $requestAuthorization = new Authorization();
        $this->translator->expects(self::once())
            ->method('getAuthorization')
            ->with($this->paymentInfo)
            ->willReturn($requestAuthorization);

        $responseAuthorization = $this->createAuthorization(
            $expectedAuthorizationState,
            $expectedAuthorizationReasonCode,
            $expectedAuthorizationValidUntil
        );

        $this->client->expects(self::once())
            ->method('authorizeOrder')
            ->with($order, $requestAuthorization, $this->apiContext)
            ->willReturn($responseAuthorization);

        $this->expectTransportException(
            'Unexpected state of payment authorization.',
            (new Context())->setPaymentInfo($this->paymentInfo)->setAuthorization($responseAuthorization)
        );

        $this->transport->authorizePayment($this->paymentInfo, $this->apiContextInfo);
    }

    private function createAuthorization(
        string $state,
        ?string $reason = null,
        ?string $validUntil = null
    ): Authorization {
        $authorization = new Authorization();
        $authorization->setState($state);
        $authorization->setReasonCode($reason);
        $authorization->setValidUntil($validUntil);

        return $authorization;
    }
}
