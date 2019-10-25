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
use Psr\Log\LoggerInterface;

abstract class AbstractTransportTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PayPalSDKObjectTranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PayPalClient
     */
    protected $client;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExceptionFactory
     */
    protected $exceptionFactory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TransportExceptionFactoryInterface
     */
    protected $paymentExceptionFactory;

    /**
     * @var PaymentInfo
     */
    protected $paymentInfo;

    /**
     * @var ApiContextInfo
     */
    protected $apiContextInfo;

    /**
     * @var ApiContext|null
     */
    protected $apiContext;

    /**
     * @var PayPalExpressTransport
     */
    protected $transport;

    protected function setUp()
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

    /**
     * @param string $paymentId
     *
     * @return PaymentInfo
     */
    protected function createPaymentInfo($paymentId = null, $orderId = null)
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

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param bool   $isSandbox
     *
     * @return ApiContextInfo
     */
    protected function createApiContextInfo($clientId, $clientSecret, $isSandbox = true)
    {
        return new ApiContextInfo(new CredentialsInfo($clientId, $clientSecret), $isSandbox);
    }

    /**
     * @param string|null $id
     * @param string|null $state
     * @param string|null $failureReason
     *
     * @return Payment
     */
    protected function createPayment($id = null, $state = null, $failureReason = null)
    {
        $payment = new Payment();
        $payment->setId($id);
        $payment->setState($state);
        $payment->setFailureReason($failureReason);
        return $payment;
    }

    /**
     * @param string $id
     * @param string $approvalLink
     *
     * @return Payment
     */
    protected function createPaymentWithApprovedLink($id, $approvalLink)
    {
        $payment = $this->createPayment($id, PayPalExpressTransport::PAYMENT_CREATED_STATUS);

        $link = new Links();
        $link->setRel(PayPalConstants::APPROVAL_URL);
        $link->setHref($approvalLink);
        $payment->setLinks([$link]);

        return $payment;
    }

    /**
     * @param Order  $order
     * @param null   $id
     * @param string $state
     * @param string $failureReason
     *
     * @return Payment
     */
    protected function createPaymentWithOrder(Order $order = null, $id = null, $state = null, $failureReason = null)
    {
        $payment = $this->createPayment($id, $state, $failureReason);

        $transaction = new Transaction();
        $relatedResource = new RelatedResources();
        $relatedResource->setOrder($order);
        $transaction->setRelatedResources([$relatedResource]);
        $payment->addTransaction($transaction);

        return $payment;
    }

    /**
     * @param                 $expectedMessage
     * @param Context         $expectedContext
     * @param \Throwable|null $expectedPrevious
     */
    protected function expectTransportException(
        $expectedMessage,
        Context $expectedContext,
        \Throwable $expectedPrevious = null
    ) {
        $expectedExceptionMessage = 'Test payment exception message';
        $expectedException = new TransportException($expectedExceptionMessage, $expectedContext->getContext());

        $this->paymentExceptionFactory->expects($this->once())
            ->method('createTransportException')
            ->with(
                $this->callback(
                    function ($message) use ($expectedMessage) {
                        $this->assertEquals(
                            $expectedMessage,
                            $message,
                            'Failed assert that createTransportException\'s argument $message equals expected value'
                        );
                        return true;
                    }
                ),
                $this->callback(
                    function ($context) use ($expectedContext) {
                        $this->assertInstanceOf(
                            Context::class,
                            $context,
                            'Failed assert that createTransportException\'s argument $context is ' . Context::class
                        );
                        $this->assertEquals(
                            $expectedContext,
                            $context,
                            'Failed assert that createTransportException\'s argument $context equals expected value'
                        );
                        return true;
                    }
                ),
                $this->callback(
                    function ($previous) use ($expectedPrevious) {
                        $this->assertEquals(
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

    /**
     * @param ApiContextInfo $apiContextInfo
     *
     * @return ApiContext
     */
    protected function expectTranslatorGetApiContext(ApiContextInfo $apiContextInfo = null)
    {
        $apiContextInfo = $apiContextInfo ?? $this->apiContextInfo;
        $this->apiContext = new ApiContext();
        $this->translator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($apiContextInfo)
            ->willReturn($this->apiContext);

        return $this->apiContext;
    }

    /**
     * @param string|null $id
     * @param string|null $state
     *
     * @return Order
     */
    protected function createOrder($id = null, $state = null)
    {
        $order = new Order();
        $order->setId($id);
        $order->setState($state);

        return $order;
    }
}
