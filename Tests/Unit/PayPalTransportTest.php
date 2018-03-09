<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalSDKObjectTranslator;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalTransport;

use PayPal\Api\Payment;
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
    protected $logger;

    /**
     * @var PayPalTransport
     */
    protected $transport;

    protected function setUp()
    {
        $this->payPalSDKObjectTranslator = $this->createMock(PayPalSDKObjectTranslator::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->transport = new PayPalTransport($this->payPalSDKObjectTranslator, $this->logger);
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

        $payment= $this->createMock(Payment::class);
        $payment->expects($this->once())
            ->method('create')
            ->with($apiContext);
        $payment->expects($this->once())
            ->method('getApprovalLink')
            ->willReturn($expectedApprovalUrl);

        $this->payPalSDKObjectTranslator
            ->expects($this->once())
            ->method('getPayment')
            ->with($paymentInfo, $successRoute, $failedRoute)
            ->willReturn($payment);

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

        $payment= $this->createMock(Payment::class);

        $payment->expects($this->once())
            ->method('create')
            ->willThrowException($expectedException);

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

        $payment= $this->createMock(Payment::class);

        $payment->expects($this->once())
            ->method('getApprovalLink')
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
}
