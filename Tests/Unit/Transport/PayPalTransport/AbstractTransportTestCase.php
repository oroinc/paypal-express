<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport\PayPalTransport;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\Context;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\TransportException;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\TransportExceptionFactoryInterface;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalClient;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalExpressTransport;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalSDKObjectTranslatorInterface;
use PayPal\Api\Links;
use PayPal\Api\Order;
use PayPal\Api\Payment;
use PayPal\Api\RelatedResources;
use PayPal\Api\Transaction;
use PayPal\Core\PayPalConstants;
use PayPal\Rest\ApiContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

abstract class AbstractTransportTestCase extends TestCase
{
    protected PayPalSDKObjectTranslatorInterface&MockObject $translator;
    protected PayPalClient&MockObject $client;
    protected LoggerInterface&MockObject $logger;
    protected ExceptionFactory&MockObject $exceptionFactory;
    protected TransportExceptionFactoryInterface&MockObject $paymentExceptionFactory;
    protected PaymentInfo $paymentInfo;
    protected ApiContextInfo $apiContextInfo;
    protected ?ApiContext $apiContext = null;
    protected PayPalExpressTransport $transport;

    #[\Override]
    protected function setUp(): void
    {
        $this->translator = $this->createMock(PayPalSDKObjectTranslatorInterface::class);
        $this->client = $this->createMock(PayPalClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->exceptionFactory = $this->createMock(ExceptionFactory::class);
        $this->paymentExceptionFactory = $this->createMock(TransportExceptionFactoryInterface::class);

        $this->paymentInfo = $this->createPaymentInfo();

        $this->apiContextInfo = $this->createApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );

        $this->transport = new PayPalExpressTransport(
            $this->translator,
            $this->client,
            $this->paymentExceptionFactory
        );
    }

    protected function createPaymentInfo(?string $paymentId = null, ?string $orderId = null): PaymentInfo
    {
        $this->paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            '123456',
            []
        );

        $this->paymentInfo->setPaymentId($paymentId);
        $this->paymentInfo->setOrderId($orderId);

        return $this->paymentInfo;
    }

    protected function createApiContextInfo(
        string $clientId,
        string $clientSecret,
        bool $isSandbox = true
    ): ApiContextInfo {
        return new ApiContextInfo(new CredentialsInfo($clientId, $clientSecret), $isSandbox);
    }

    protected function createPayment(
        ?string $id = null,
        ?string $state = null,
        ?string $failureReason = null
    ): Payment {
        $payment = new Payment();
        $payment->setId($id);
        $payment->setState($state);
        $payment->setFailureReason($failureReason);

        return $payment;
    }

    protected function createPaymentWithApprovedLink(string $id, string $approvalLink): Payment
    {
        $payment = $this->createPayment($id, PayPalExpressTransport::PAYMENT_CREATED_STATUS);

        $link = new Links();
        $link->setRel(PayPalConstants::APPROVAL_URL);
        $link->setHref($approvalLink);
        $payment->setLinks([$link]);

        return $payment;
    }

    protected function createPaymentWithOrder(
        ?Order $order = null,
        ?string $id = null,
        ?string $state = null,
        ?string $failureReason = null
    ): Payment {
        $payment = $this->createPayment($id, $state, $failureReason);

        $transaction = new Transaction();
        $relatedResource = new RelatedResources();
        $relatedResource->setOrder($order);
        $transaction->setRelatedResources([$relatedResource]);
        $payment->addTransaction($transaction);

        return $payment;
    }

    protected function expectTransportException(
        $expectedMessage,
        Context $expectedContext,
        ?\Throwable $expectedPrevious = null
    ): void {
        $expectedExceptionMessage = 'Test payment exception message';
        $expectedException = new TransportException($expectedExceptionMessage, $expectedContext->getContext());

        $this->paymentExceptionFactory->expects(self::once())
            ->method('createTransportException')
            ->with(
                self::callback(
                    function ($message) use ($expectedMessage) {
                        self::assertEquals(
                            $expectedMessage,
                            $message,
                            'Failed assert that createTransportException\'s argument $message equals expected value'
                        );

                        return true;
                    }
                ),
                self::callback(
                    function ($context) use ($expectedContext) {
                        self::assertInstanceOf(
                            Context::class,
                            $context,
                            'Failed assert that createTransportException\'s argument $context is ' . Context::class
                        );
                        self::assertEquals(
                            $expectedContext,
                            $context,
                            'Failed assert that createTransportException\'s argument $context equals expected value'
                        );

                        return true;
                    }
                ),
                self::callback(
                    function ($previous) use ($expectedPrevious) {
                        self::assertEquals(
                            $expectedPrevious,
                            $previous,
                            'Failed assert that createTransportException\'s argument $previous equals value'
                        );

                        return true;
                    }
                )
            )
            ->willThrowException($expectedException);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
    }

    protected function expectTranslatorGetApiContext(?ApiContextInfo $apiContextInfo = null): ApiContext
    {
        $apiContextInfo = $apiContextInfo ?? $this->apiContextInfo;
        $this->apiContext = new ApiContext();
        $this->translator->expects(self::once())
            ->method('getApiContext')
            ->with($apiContextInfo)
            ->willReturn($this->apiContext);

        return $this->apiContext;
    }

    protected function createOrder(?string $id = null, ?string $state = null): Order
    {
        $order = new Order();
        $order->setId($id);
        $order->setState($state);

        return $order;
    }
}
