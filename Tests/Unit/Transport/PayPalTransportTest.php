<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport;

use Oro\Bundle\PayPalExpressBundle\Exception\ConnectionException;
use Oro\Bundle\PayPalExpressBundle\Exception\OperationExecutionFailedException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
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
     * @var PayPalTransport
     */
    protected $transport;

    protected function setUp()
    {
        $this->payPalSDKObjectTranslator = $this->createMock(PayPalSDKObjectTranslatorInterface::class);
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
        $payPalConnectionException = new PayPalConnectionException(
            'https://api.sandbox.paypal.com/v1/payments/payment',
            'Internal Server Error'
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
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

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
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

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
        $apiContext = $this->setupApiContextTranslator($apiContextInfo);

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
            ->with($orderId)
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Order Id is required.');

        $this->transport->authorizePayment($paymentInfo, $apiContextInfo);
    }

    public function testAuthorizeShouldLogPayPalConnectionExceptionAndThrowOwnExceptionInReplaceOfSDK()
    {
        $payPalConnectionException = new PayPalConnectionException(
            'https://api.sandbox.paypal.com/v1/payments/payment',
            'Internal Server Error'
        );

        $orderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo(null, $orderId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $this->setupApiContextTranslator($apiContextInfo);

        $order = $this->getOrder();
        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($orderId)
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

        $this->transport->authorizePayment($paymentInfo, $apiContextInfo);
    }

    public function testAutorizeShouldLogExceptionsAndThrowOwnExceptionsInReplaceOfSDKExceptions()
    {
        $exception = new \Exception('Fatal Error');

        $orderId = '3xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = $this->getPaymentInfo(null, $orderId);

        $apiContextInfo = $this->getApiContextInfo(
            'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ'
        );
        $this->setupApiContextTranslator($apiContextInfo);

        $order = $this->getOrder();
        $this->client
            ->expects($this->once())
            ->method('getOrderById')
            ->with($orderId)
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

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not authorize payment. Reason: Fatal Error',
                [
                    'exception' => $exception
                ]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not authorize payment.');

        $this->transport->authorizePayment($paymentInfo, $apiContextInfo);
    }

    public function testAuthorizeShouldThrowAnExceptionIfAuthorizeRequestIsFailed()
    {
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
            ->with($orderId)
            ->willReturn($order);

        $authorization = new Authorization();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getAuthorization')
            ->with($paymentInfo)
            ->willReturn($authorization);

        $status = 'expired';
        $responseAuthorization = $this->getAuthorization($status);
        $this->client->expects($this->once())
            ->method('authorizeOrder')
            ->with($order, $authorization, $apiContext)
            ->willReturn($responseAuthorization);

        $this->expectExceptionMessage(
            "Could not authorize payment {$paymentId}. Authorization status: {$status}."
        );
        $this->expectException(OperationExecutionFailedException::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not authorize payment.',
                [
                    'paymentId'           => $paymentId,
                    'authorization state' => $status
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
            ->with($orderId)
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Order Id is required.');

        $this->transport->capturePayment($paymentInfo, $apiContextInfo);
    }

    public function testCaptureShouldLogPayPalConnectionExceptionAndThrowOwnExceptionInReplaceOfSDK()
    {
        $payPalConnectionException = new PayPalConnectionException(
            'https://api.sandbox.paypal.com/v1/payments/payment',
            'Internal Server Error'
        );

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
            ->with($orderId)
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

        $this->transport->capturePayment($paymentInfo, $apiContextInfo);
    }

    public function testCaptureShouldLogExceptionsAndThrowOwnExceptionsInReplaceOfSDKExceptions()
    {
        $exception = new \Exception('Fatal Error');

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
            ->with($orderId)
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

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not capture payment. Reason: Fatal Error',
                [
                    'exception' => $exception
                ]
            );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not capture payment.');

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
            ->with($orderId)
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

        $this->expectExceptionMessage(
            "Could not capture payment {$paymentId}. Capture status: {$status}."
        );
        $this->expectException(OperationExecutionFailedException::class);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not capture payment.',
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
