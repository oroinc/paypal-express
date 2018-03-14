<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport;

use Oro\Bundle\PayPalExpressBundle\Exception\ConnectionException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalClient;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalSDKObjectTranslator;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalTransport;

use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\Links;
use PayPal\Api\Order;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RelatedResources;
use PayPal\Api\Transaction;
use PayPal\Core\PayPalConstants;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

use Psr\Log\LoggerInterface;

class PayPalTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PayPalSDKObjectTranslator
     */
    protected $payPalSDKObjectTranslator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PayPalClient
     */
    protected $client;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var PayPalTransport
     */
    protected $transport;

    protected function setUp()
    {
        $this->payPalSDKObjectTranslator = $this->createMock(PayPalSDKObjectTranslator::class);
        $this->client = $this->createMock(PayPalClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->transport = new PayPalTransport($this->payPalSDKObjectTranslator, $this->client, $this->logger);
    }

    public function testSetupPayment()
    {
        $expectedApprovalUrl = 'https://paypal.com/payment/approve';

        $paymentInfo = $this->getPaymentInfo();

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );

        $redirectRoutesInfo = $this->getRedirectionRoutesInfo(
            'text.example.com/paypal/success',
            'text.example.com/paypal/failed'
        );

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($apiContextInfo)
            ->willReturn($apiContext);

        $payment = new Payment();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getPayment')
            ->with($paymentInfo, $redirectRoutesInfo)
            ->willReturn($payment);

        $executedPayment = new Payment();

        $link = new Links();
        $link->setRel(PayPalConstants::APPROVAL_URL);
        $link->setHref($expectedApprovalUrl);
        $executedPayment->setLinks([$link]);

        $this->client->expects($this->once())
            ->method('createPayment')
            ->with($payment, $apiContext)
            ->willReturn($executedPayment);

        $approvalUrl = $this->transport->setupPayment($paymentInfo, $apiContextInfo, $redirectRoutesInfo);
        $this->assertEquals($expectedApprovalUrl, $approvalUrl);
    }

    public function testSetupPaymentShouldLogPayPalConnectionExceptionAndThrowOwnExceptionInReplaceOfSDK()
    {
        $payPalConnectionException = new PayPalConnectionException(
            'https://api.sandbox.paypal.com/v1/payments/payment',
            'Internal Server Error'
        );

        $paymentInfo = $this->getPaymentInfo();

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($apiContextInfo)
            ->willReturn($apiContext);

        $this->client->expects($this->once())
            ->method('createPayment')
            ->willThrowException($payPalConnectionException);

        $redirectRoutesInfo = $this->getRedirectionRoutesInfo(
            'text.example.com/paypal/success',
            'text.example.com/paypal/failed'
        );

        $payment = new Payment();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getPayment')
            ->with($paymentInfo, $redirectRoutesInfo)
            ->willReturn($payment);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not connect to PayPal server. Reason: Internal Server Error',
                [
                    'exception' => $payPalConnectionException
                ]
            );

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Could not connect to PayPal server.');

        $this->transport->setupPayment($paymentInfo, $apiContextInfo, $redirectRoutesInfo);
    }

    public function testSetupPaymentShouldLogExceptionsAndThrowOwnExceptionsInReplaceOfSDKExceptions()
    {
        $exception = new \Exception('Fatal Error');

        $paymentInfo = $this->getPaymentInfo();

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );

        $redirectRoutesInfo = $this->getRedirectionRoutesInfo(
            'text.example.com/paypal/success',
            'text.example.com/paypal/failed'
        );

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($apiContextInfo)
            ->willReturn($apiContext);

        $payment = new Payment();

        $this->client->expects($this->once())
            ->method('createPayment')
            ->willThrowException($exception);

        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getPayment')
            ->with($paymentInfo, $redirectRoutesInfo)
            ->willReturn($payment);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not create payment for PayPal. Reason: Fatal Error',
                [
                    'exception' => $exception
                ]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not create payment for PayPal.');

        $this->transport->setupPayment($paymentInfo, $apiContextInfo, $redirectRoutesInfo);
    }

    public function testExecutePayment()
    {
        $paymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentInfo = $this->getPaymentInfo($paymentId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($apiContextInfo)
            ->willReturn($apiContext);

        $execution = new PaymentExecution();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getPaymentExecution')
            ->with($paymentInfo)
            ->willReturn($execution);

        $authorization = new Authorization();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getAuthorization')
            ->with($paymentInfo)
            ->willReturn($authorization);

        $capture = new Capture();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getCapturedDetails')
            ->with($paymentInfo)
            ->willReturn($capture);

        $order = new Order();
        $payment = $this->getPayment($order);
        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($paymentId, $apiContext)
            ->willReturn($payment);

        $this->client->expects($this->once())
            ->method('executePayment')
            ->with($payment, $execution, $apiContext);

        $this->client->expects($this->once())
            ->method('authorizeOrder')
            ->with($order, $authorization, $apiContext);

        $this->client->expects($this->once())
            ->method('captureOrder')
            ->with($order, $capture, $apiContext);

        $this->transport->executePayment($paymentInfo, $apiContextInfo);
    }

    public function testExecutePaymentShouldLogPayPalConnectionExceptionAndThrowOwnExceptionInReplaceOfSDK()
    {
        $payPalConnectionException = new PayPalConnectionException(
            'https://api.sandbox.paypal.com/v1/payments/payment',
            'Internal Server Error'
        );

        $paymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentInfo = $this->getPaymentInfo($paymentId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($apiContextInfo)
            ->willReturn($apiContext);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($paymentId, $apiContext)
            ->willThrowException($payPalConnectionException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not connect to PayPal server. Reason: Internal Server Error',
                [
                    'exception' => $payPalConnectionException
                ]
            );

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Could not connect to PayPal server.');

        $this->transport->executePayment($paymentInfo, $apiContextInfo);
    }

    public function testExecutePaymentShouldLogExceptionsAndThrowOwnExceptionsInReplaceOfSDKExceptions()
    {
        $exception = new \Exception('Fatal Error');
        $paymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo($paymentId);
        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($apiContextInfo)
            ->willReturn($apiContext);

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($apiContextInfo)
            ->willReturn($apiContext);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($paymentId, $apiContext)
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not execute payment. Reason: Fatal Error',
                [
                    'exception' => $exception
                ]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not execute payment.');

        $this->transport->executePayment($paymentInfo, $apiContextInfo);
    }

    /**
     * @param Order $order
     *
     * @return Payment
     */
    protected function getPayment(Order $order)
    {
        $payment = new Payment();
        $transaction = new Transaction();
        $relatedResource = new RelatedResources();
        $relatedResource->setOrder($order);
        $transaction->setRelatedResources([$relatedResource]);
        $payment->addTransaction($transaction);

        return $payment;
    }

    /**
     * @param string $paymentId
     *
     * @return PaymentInfo
     */
    protected function getPaymentInfo($paymentId = null)
    {
        return new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            [],
            $paymentId
        );
    }

    /**
     * @param string  $clientId
     * @param string  $clientSecret
     * @param bool    $isSandbox
     *
     * @return ApiContextInfo
     */
    protected function getApiContextInfo($clientId, $clientSecret, $isSandbox = true)
    {
        return new ApiContextInfo(new CredentialsInfo($clientId, $clientSecret), $isSandbox);
    }

    /**
     * @param string $successRoute
     * @param string $failedRoute
     *
     * @return RedirectRoutesInfo
     */
    protected function getRedirectionRoutesInfo($successRoute, $failedRoute)
    {
        return new RedirectRoutesInfo($successRoute, $failedRoute);
    }
}
