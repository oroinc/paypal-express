<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport;

use Oro\Bundle\PayPalExpressBundle\Exception\ConnectionException;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\OperationExecutionFailedException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ExceptionInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalClient;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalSDKObjectTranslatorInterface;
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

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PayPalTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PayPalSDKObjectTranslatorInterface
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
     * @var \PHPUnit_Framework_MockObject_MockObject|ExceptionFactory
     */
    protected $exceptionFactory;

    /**
     * @var PayPalTransport
     */
    protected $transport;

    protected function setUp()
    {
        $this->payPalSDKObjectTranslator = $this->createMock(PayPalSDKObjectTranslatorInterface::class);
        $this->client = $this->createMock(PayPalClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->exceptionFactory = $this->createMock(ExceptionFactory::class);

        $this->transport = new PayPalTransport(
            $this->payPalSDKObjectTranslator,
            $this->client,
            $this->logger,
            $this->exceptionFactory
        );
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
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $payment = new Payment();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getPayment')
            ->with($paymentInfo, $redirectRoutesInfo)
            ->willReturn($payment);

        $executedPayment = new Payment();
        $executedPayment->setState(PayPalTransport::PAYMENT_CREATED_STATUS);

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
        $payPalSdkExceptionMessage = 'Internal Server Error';
        $expectedStatusCode = 'AGREEMENT_ALREADY_CANCELLED';
        $expectedDetails = 'Exception description';
        $expectedLink = 'https://developer.paypal.com/docs/api/payments/#errors';
        $debugId = 'PxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $payPalConnectionException = new PayPalConnectionException(
            'https://api.sandbox.paypal.com/v1/payments/payment',
            $payPalSdkExceptionMessage
        );

        $paymentInfo = $this->getPaymentInfo();

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $this->setupApiContextTranslator($apiContextInfo);

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

        $exceptionInfo = new ExceptionInfo(
            $payPalSdkExceptionMessage,
            $expectedStatusCode,
            $expectedDetails,
            $expectedLink,
            $debugId,
            $paymentInfo
        );
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getExceptionInfo')
            ->with($payPalConnectionException, $paymentInfo)
            ->willReturn($exceptionInfo);

        $expectedExceptionMessage = 'Could not create payment.';
        $connectionException = new ConnectionException($expectedExceptionMessage);
        $this->exceptionFactory->expects($this->once())
            ->method('createConnectionException')
            ->with('Could not create payment', $exceptionInfo, $payPalConnectionException)
            ->willReturn($connectionException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                "Could not create payment. [Reason: {$payPalSdkExceptionMessage}, Code: {$expectedStatusCode}, " .
                "Payment Id:  Details: {$expectedDetails}, Informational Link: {$expectedLink} Debug Id: {$debugId}].",
                [
                    'exception' => $payPalConnectionException,
                    'exceptionInfo' => $exceptionInfo
                ]
            );

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->transport->setupPayment($paymentInfo, $apiContextInfo, $redirectRoutesInfo);
    }

    public function testSetupPaymentShouldLogExceptionsAndThrowOwnExceptionsInReplaceOfSDKExceptions()
    {
        $payPalSDKExceptionMessage = 'Fatal Error';
        $exception = new \Exception($payPalSDKExceptionMessage);

        $paymentInfo = $this->getPaymentInfo();

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );

        $redirectRoutesInfo = $this->getRedirectionRoutesInfo(
            'text.example.com/paypal/success',
            'text.example.com/paypal/failed'
        );
        $this->setupApiContextTranslator($apiContextInfo);

        $payment = new Payment();

        $this->client->expects($this->once())
            ->method('createPayment')
            ->willThrowException($exception);

        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getPayment')
            ->with($paymentInfo, $redirectRoutesInfo)
            ->willReturn($payment);

        $expectedMessage = "Could not create payment. Reason: {$payPalSDKExceptionMessage}";
        $runtimeException = new RuntimeException($expectedMessage);
        $this->exceptionFactory->expects($this->once())
            ->method('createRuntimeException')
            ->with($expectedMessage)
            ->willReturn($runtimeException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $expectedMessage,
                [
                    'exception' => $exception
                ]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

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
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $execution = new PaymentExecution();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getPaymentExecution')
            ->with($paymentInfo)
            ->willReturn($execution);

        $order = new Order();
        $payment = new Payment();
        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($paymentId, $apiContext)
            ->willReturn($payment);

        $executedPayment = $this->getPayment($order, PayPalTransport::PAYMENT_EXECUTED_STATUS);

        $this->client->expects($this->once())
            ->method('executePayment')
            ->with($payment, $execution, $apiContext)
            ->willReturn($executedPayment);

        $this->transport->executePayment($paymentInfo, $apiContextInfo);
    }

    public function testExecutePaymentShouldLogPayPalConnectionExceptionAndThrowOwnExceptionInReplaceOfSDK()
    {
        $payPalSDKExceptionMessage = 'Internal Server Error';
        $expectedStatusCode = 'AGREEMENT_ALREADY_CANCELLED';
        $expectedDetails = 'Exception description';
        $expectedLink = 'https://developer.paypal.com/docs/api/payments/#errors';
        $debugId = 'PxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $payPalConnectionException = new PayPalConnectionException(
            'https://api.sandbox.paypal.com/v1/payments/payment',
            $payPalSDKExceptionMessage
        );

        $paymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentInfo = $this->getPaymentInfo($paymentId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($paymentId, $apiContext)
            ->willThrowException($payPalConnectionException);
        $exceptionInfo = new ExceptionInfo(
            $payPalSDKExceptionMessage,
            $expectedStatusCode,
            $expectedDetails,
            $expectedLink,
            $debugId,
            $paymentInfo
        );
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getExceptionInfo')
            ->with($payPalConnectionException, $paymentInfo)
            ->willReturn($exceptionInfo);

        $expectedExceptionMessage = 'Could not execute payment.';
        $connectionException = new ConnectionException($expectedExceptionMessage);
        $this->exceptionFactory->expects($this->once())
            ->method('createConnectionException')
            ->with('Could not execute payment', $exceptionInfo, $payPalConnectionException)
            ->willReturn($connectionException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                "Could not execute payment. [Reason: {$payPalSDKExceptionMessage}, Code: {$expectedStatusCode}, " .
                "Payment Id: {$paymentId} Details: {$expectedDetails}, " .
                "Informational Link: {$expectedLink} Debug Id: {$debugId}].",
                [
                    'exception' => $payPalConnectionException,
                    'exceptionInfo' => $exceptionInfo
                ]
            );

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->transport->executePayment($paymentInfo, $apiContextInfo);
    }

    public function testExecutePaymentShouldLogExceptionsAndThrowOwnExceptionsInReplaceOfSDKExceptions()
    {
        $payPalSDKExceptionMessage = 'Fatal Error';
        $exception = new \Exception($payPalSDKExceptionMessage);
        $paymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo($paymentId);
        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($paymentId, $apiContext)
            ->willThrowException($exception);

        $expectedMessage = "Could not execute payment. Reason: {$payPalSDKExceptionMessage}";
        $runtimeException = new RuntimeException($expectedMessage);
        $this->exceptionFactory->expects($this->once())
            ->method('createRuntimeException')
            ->with($expectedMessage)
            ->willReturn($runtimeException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $expectedMessage,
                [
                    'exception' => $exception
                ]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->transport->executePayment($paymentInfo, $apiContextInfo);
    }

    public function testAuthorize()
    {
        $orderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo(null, $orderId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $order = $this->getOrder();
        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($orderId, $apiContext)
            ->willReturn($order);

        $authorization = new Authorization();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getAuthorization')
            ->with($paymentInfo)
            ->willReturn($authorization);

        $authorizedOrder = $this->getOrder(PayPalTransport::ORDER_PAYMENT_AUTHORIZED_STATUS);
        $this->client->expects($this->once())
            ->method('authorizeOrder')
            ->with($order, $authorization, $apiContext)
            ->willReturn($authorizedOrder);

        $this->transport->authorizePayment($paymentInfo, $apiContextInfo);
    }

    public function testAuthorizeShouldThrowExceptionIfOrderIdIsNotDefined()
    {
        $paymentInfo = $this->getPaymentInfo();

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );

        $expectedMessage = 'Order Id is required.';

        $this->exceptionFactory->expects($this->once())
            ->method('createRuntimeException')
            ->with($expectedMessage)
            ->willReturn(new RuntimeException($expectedMessage));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->transport->authorizePayment($paymentInfo, $apiContextInfo);
    }

    public function testAuthorizeShouldLogPayPalConnectionExceptionAndThrowOwnExceptionInReplaceOfSDK()
    {
        $payPalSDKExceptionMessage = 'Internal Server Error';
        $expectedStatusCode = 'AGREEMENT_ALREADY_CANCELLED';
        $expectedDetails = 'Exception description';
        $expectedLink = 'https://developer.paypal.com/docs/api/payments/#errors';
        $debugId = 'PxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $payPalConnectionException = new PayPalConnectionException(
            'https://api.sandbox.paypal.com/v1/payments/payment',
            'Internal Server Error'
        );

        $paymentId = '1xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $orderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo($paymentId, $orderId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $order = $this->getOrder();
        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($orderId, $apiContext)
            ->willReturn($order);

        $authorization = new Authorization();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getAuthorization')
            ->with($paymentInfo)
            ->willReturn($authorization);

        $this->client->expects($this->once())
            ->method('authorizeOrder')
            ->willThrowException($payPalConnectionException);
        $exceptionInfo = new ExceptionInfo(
            $payPalSDKExceptionMessage,
            $expectedStatusCode,
            $expectedDetails,
            $expectedLink,
            $debugId,
            $paymentInfo
        );
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getExceptionInfo')
            ->with($payPalConnectionException, $paymentInfo)
            ->willReturn($exceptionInfo);

        $expectedExceptionMessage = 'Could not authorize payment.';
        $connectionException = new ConnectionException($expectedExceptionMessage);
        $this->exceptionFactory->expects($this->once())
            ->method('createConnectionException')
            ->with('Could not authorize payment', $exceptionInfo, $payPalConnectionException)
            ->willReturn($connectionException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                "Could not authorize payment. [Reason: {$payPalSDKExceptionMessage}, Code: {$expectedStatusCode}, " .
                "Payment Id: {$paymentId} Details: {$expectedDetails}, Informational Link: {$expectedLink}" .
                " Debug Id: {$debugId}].",
                [
                    'exception' => $payPalConnectionException,
                    'exceptionInfo' => $exceptionInfo
                ]
            );

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->transport->authorizePayment($paymentInfo, $apiContextInfo);
    }

    public function testAuthorizeShouldLogExceptionsAndThrowOwnExceptionsInReplaceOfSDKExceptions()
    {
        $payPalSDKExceptionMessage = 'Fatal Error';
        $exception = new \Exception($payPalSDKExceptionMessage);

        $orderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo(null, $orderId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $order = $this->getOrder();
        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($orderId, $apiContext)
            ->willReturn($order);

        $authorization = new Authorization();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getAuthorization')
            ->with($paymentInfo)
            ->willReturn($authorization);

        $this->client->expects($this->once())
            ->method('authorizeOrder')
            ->willThrowException($exception);

        $expectedMessage = "Could not authorize payment. Reason: {$payPalSDKExceptionMessage}";
        $runtimeException = new RuntimeException($expectedMessage);
        $this->exceptionFactory->expects($this->once())
            ->method('createRuntimeException')
            ->with($expectedMessage)
            ->willReturn($runtimeException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $expectedMessage,
                [
                    'exception' => $exception
                ]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->transport->authorizePayment($paymentInfo, $apiContextInfo);
    }

    public function testAuthorizeShouldThrowAnExceptionIfAuthorizeRequestIsFailed()
    {
        $message = 'Could not authorize payment.';

        $orderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentId = '1xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo($paymentId, $orderId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $order = $this->getOrder();
        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($orderId, $apiContext)
            ->willReturn($order);

        $authorization = new Authorization();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getAuthorization')
            ->with($paymentInfo)
            ->willReturn($authorization);

        $status = 'expired';
        $responseAuthorization = $this->getAuthorization($status);
        $reason = 'AUTHORIZATION';
        $responseAuthorization->setReasonCode($reason);
        $validUntil = '2018-01-01';
        $responseAuthorization->setValidUntil($validUntil);

        $this->client->expects($this->once())
            ->method('authorizeOrder')
            ->with($order, $authorization, $apiContext)
            ->willReturn($responseAuthorization);

        $expectedMessage = "$message. Reason {$reason}";
        $this->exceptionFactory->expects($this->once())
            ->method('createOperationExecutionFailedException')
            ->with($message, $reason)
            ->willReturn(new OperationExecutionFailedException($expectedMessage));

        $this->expectExceptionMessage($expectedMessage);
        $this->expectException(OperationExecutionFailedException::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not authorize payment.',
                [
                    'paymentId'           => $paymentId,
                    'authorization state' => $status,
                    'reason code'         => $reason,
                    'valid until'         => $validUntil,
                ]
            );

        $this->transport->authorizePayment($paymentInfo, $apiContextInfo);
    }

    public function testCapture()
    {
        $orderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo(null, $orderId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );

        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $order = $this->getOrder();
        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($orderId, $apiContext)
            ->willReturn($order);

        $capture = new Capture();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getCapturedDetails')
            ->with($paymentInfo)
            ->willReturn($capture);

        $responseCapture = $this->getCapture(PayPalTransport::ORDER_PAYMENT_CAPTURED_STATUS);
        $this->client->expects($this->once())
            ->method('captureOrder')
            ->with($order, $capture, $apiContext)
            ->willReturn($responseCapture);

        $this->transport->capturePayment($paymentInfo, $apiContextInfo);
    }

    public function testCaptureShouldThrowExceptionIfOrderIdIsNotDefined()
    {
        $paymentInfo = $this->getPaymentInfo();

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $expectedMessage = 'Order Id is required.';

        $this->exceptionFactory->expects($this->once())
            ->method('createRuntimeException')
            ->with($expectedMessage)
            ->willReturn(new RuntimeException($expectedMessage));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->transport->capturePayment($paymentInfo, $apiContextInfo);
    }

    public function testCaptureShouldLogPayPalConnectionExceptionAndThrowOwnExceptionInReplaceOfSDK()
    {
        $payPalSdkExceptionMessage = 'Internal Server Error';
        $expectedStatusCode = 'AGREEMENT_ALREADY_CANCELLED';
        $expectedDetails = 'Exception description';
        $expectedLink = 'https://developer.paypal.com/docs/api/payments/#errors';
        $debugId = 'PxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $payPalConnectionException = new PayPalConnectionException(
            'https://api.sandbox.paypal.com/v1/payments/payment',
            'Internal Server Error'
        );

        $paymentId = '1xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $orderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo($paymentId, $orderId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $order = $this->getOrder();
        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($orderId, $apiContext)
            ->willReturn($order);

        $capture = new Capture();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getCapturedDetails')
            ->with($paymentInfo)
            ->willReturn($capture);

        $this->client->expects($this->once())
            ->method('captureOrder')
            ->with($order, $capture, $apiContext)
            ->willThrowException($payPalConnectionException);
        $exceptionInfo = new ExceptionInfo(
            $payPalSdkExceptionMessage,
            $expectedStatusCode,
            $expectedDetails,
            $expectedLink,
            $debugId,
            $paymentInfo
        );
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getExceptionInfo')
            ->with($payPalConnectionException, $paymentInfo)
            ->willReturn($exceptionInfo);

        $expectedExceptionMessage = 'Could not capture payment.';
        $connectionException = new ConnectionException($expectedExceptionMessage);
        $this->exceptionFactory->expects($this->once())
            ->method('createConnectionException')
            ->with('Could not capture payment', $exceptionInfo, $payPalConnectionException)
            ->willReturn($connectionException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                "Could not capture payment. [Reason: {$payPalSdkExceptionMessage}, Code: {$expectedStatusCode}, " .
                "Payment Id: {$paymentId} Details: {$expectedDetails}, Informational Link: {$expectedLink} " .
                "Debug Id: {$debugId}].",
                [
                    'exception' => $payPalConnectionException,
                    'exceptionInfo' => $exceptionInfo
                ]
            );

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->transport->capturePayment($paymentInfo, $apiContextInfo);
    }

    public function testCaptureShouldLogExceptionsAndThrowOwnExceptionsInReplaceOfSDKExceptions()
    {
        $payPalSDKMessage = 'Fatal Error';
        $exception = new \Exception($payPalSDKMessage);

        $orderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo(null, $orderId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $order = $this->getOrder();
        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($orderId, $apiContext)
            ->willReturn($order);

        $capture = new Capture();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getCapturedDetails')
            ->with($paymentInfo)
            ->willReturn($capture);

        $this->client->expects($this->once())
            ->method('captureOrder')
            ->with($order, $capture, $apiContext)
            ->willThrowException($exception);

        $expectedMessage = "Could not capture payment. Reason: {$payPalSDKMessage}";
        $runtimeException = new RuntimeException($expectedMessage);
        $this->exceptionFactory->expects($this->once())
            ->method('createRuntimeException')
            ->with($expectedMessage)
            ->willReturn($runtimeException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $expectedMessage,
                [
                    'exception' => $exception
                ]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->transport->capturePayment($paymentInfo, $apiContextInfo);
    }

    public function testCaptureShouldThrowAnErrorIfCapturingIsFailed()
    {
        $orderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo($paymentId, $orderId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );

        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

        $order = $this->getOrder();
        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($orderId, $apiContext)
            ->willReturn($order);

        $capture = new Capture();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getCapturedDetails')
            ->with($paymentInfo)
            ->willReturn($capture);

        $status = 'refunded';
        $responseCapture = $this->getCapture($status);
        $this->client->expects($this->once())
            ->method('captureOrder')
            ->with($order, $capture, $apiContext)
            ->willReturn($responseCapture);

        $expectedMessage = 'Could not capture payment.';
        $this->exceptionFactory->expects($this->once())
            ->method('createOperationExecutionFailedException')
            ->with($expectedMessage, null)
            ->willReturn(new OperationExecutionFailedException($expectedMessage));

        $this->expectExceptionMessage($expectedMessage);
        $this->expectException(OperationExecutionFailedException::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $expectedMessage,
                [
                    'paymentId'     => $paymentId,
                    'capture state' => $status
                ]
            );

        $this->transport->capturePayment($paymentInfo, $apiContextInfo);
    }

    /**
     * @param ApiContextInfo $apiContextInfo
     *
     * @return ApiContext
     */
    protected function setupApiContextTranslator(ApiContextInfo $apiContextInfo)
    {
        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($apiContextInfo)
            ->willReturn($apiContext);

        return $apiContext;
    }

    /**
     * @param Order  $order
     * @param string $state
     *
     * @return Payment
     */
    protected function getPayment(Order $order, $state = null)
    {
        $payment = new Payment();
        $transaction = new Transaction();
        $relatedResource = new RelatedResources();
        $relatedResource->setOrder($order);
        $transaction->setRelatedResources([$relatedResource]);
        $payment->addTransaction($transaction);

        if ($state) {
            $payment->setState($state);
        }

        return $payment;
    }

    /**
     * @param string|null $state
     *
     * @return Order
     */
    protected function getOrder($state = null)
    {
        $order = new Order();
        $order->setState($state);

        return $order;
    }

    /**
     * @param string|null $status
     *
     * @return Authorization
     */
    protected function getAuthorization($status = null)
    {
        $authorization = new Authorization();
        $authorization->setState($status);

        return $authorization;
    }

    /**
     * @param $status
     * @return Capture
     */
    protected function getCapture($status = null)
    {
        $capture = new Capture();
        $capture->setState($status);

        return $capture;
    }

    /**
     * @param string $paymentId
     *
     * @return PaymentInfo
     */
    protected function getPaymentInfo($paymentId = null, $orderId = null)
    {
        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            []
        );

        $paymentInfo->setPaymentId($paymentId);
        $paymentInfo->setOrderId($orderId);

        return $paymentInfo;
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
