<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $payPalSDKObjectTranslator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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
        $successRoute = 'text.example.com/paypal/success';
        $failedRoute = 'text.example.com/paypal/failed';
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            []
        );
        $credentialsInfo = new CredentialsInfo($clientId, $clientSecret);

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($credentialsInfo)
            ->willReturn($apiContext);

        $payment = new Payment();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getPayment')
            ->with($paymentInfo, $successRoute, $failedRoute)
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

        $approvalUrl = $this->transport->setupPayment($paymentInfo, $credentialsInfo, $successRoute, $failedRoute);
        $this->assertEquals($expectedApprovalUrl, $approvalUrl);
    }

    public function testSetupPaymentShouldLogPayPalConnectionExceptionAndRethrowIt()
    {
        $expectedExceptionMessage = 'Internal Server Error';
        $expectedException = new PayPalConnectionException(
            'https://api.sandbox.paypal.com/v1/payments/payment',
            $expectedExceptionMessage
        );
        $successRoute = 'text.example.com/paypal/success';
        $failedRoute = 'text.example.com/paypal/failed';
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            []
        );
        $credentialsInfo = new CredentialsInfo($clientId, $clientSecret);

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($credentialsInfo)
            ->willReturn($apiContext);

        $this->client->expects($this->once())
            ->method('createPayment')
            ->willThrowException($expectedException);

        $payment = new Payment();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getPayment')
            ->with($paymentInfo, $successRoute, $failedRoute)
            ->willReturn($payment);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not connect to PayPal server. Reason: Internal Server Error',
                [
                    'exception' => $expectedException
                ]
            );

        $this->expectException(PayPalConnectionException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->transport->setupPayment($paymentInfo, $credentialsInfo, $successRoute, $failedRoute);
    }

    public function testSetupPaymentShouldLogExceptionsAndRethrowThem()
    {
        $expectedExceptionMessage = 'Fatal Error';
        $expectedException = new \Exception($expectedExceptionMessage);
        $successRoute = 'text.example.com/paypal/success';
        $failedRoute = 'text.example.com/paypal/failed';
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            []
        );
        $credentialsInfo = new CredentialsInfo($clientId, $clientSecret);

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($credentialsInfo)
            ->willReturn($apiContext);

        $payment = new Payment();

        $this->client->expects($this->once())
            ->method('createPayment')
            ->willThrowException($expectedException);

        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getPayment')
            ->with($paymentInfo, $successRoute, $failedRoute)
            ->willReturn($payment);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not create payment for PayPal. Reason: Fatal Error',
                [
                    'exception' => $expectedException
                ]
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->transport->setupPayment($paymentInfo, $credentialsInfo, $successRoute, $failedRoute);
    }

    public function testExecutePayment()
    {
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            [],
            $paymentId
        );
        $credentialsInfo = new CredentialsInfo($clientId, $clientSecret);

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($credentialsInfo)
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

        $this->transport->executePayment($paymentInfo, $credentialsInfo);
    }

    public function testExecutePaymentShouldLogPayPalConnectionExceptionAndRethrowIt()
    {
        $expectedExceptionMessage = 'Internal Server Error';
        $expectedException = new PayPalConnectionException(
            'https://api.sandbox.paypal.com/v1/payments/payment',
            $expectedExceptionMessage
        );

        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            [],
            $paymentId
        );
        $credentialsInfo = new CredentialsInfo($clientId, $clientSecret);

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($credentialsInfo)
            ->willReturn($apiContext);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($paymentId, $apiContext)
            ->willThrowException($expectedException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not connect to PayPal server. Reason: Internal Server Error',
                [
                    'exception' => $expectedException
                ]
            );

        $this->expectException(PayPalConnectionException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->transport->executePayment($paymentInfo, $credentialsInfo);
    }

    public function testExecutePaymentShouldLogExceptionsAndRethrowThem()
    {
        $expectedExceptionMessage = 'Fatal Error';
        $expectedException = new \Exception($expectedExceptionMessage);
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            [],
            $paymentId
        );
        $credentialsInfo = new CredentialsInfo($clientId, $clientSecret);

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($credentialsInfo)
            ->willReturn($apiContext);

        $apiContext = new ApiContext();
        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getApiContext')
            ->with($credentialsInfo)
            ->willReturn($apiContext);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($paymentId, $apiContext)
            ->willThrowException($expectedException);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Could not execute payment. Reason: Fatal Error',
                [
                    'exception' => $expectedException
                ]
            );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->transport->executePayment($paymentInfo, $credentialsInfo);
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
}
