<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport\PayPalTransport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\Context;

class SetupPaymentTest extends AbstractTransportTestCase
{
    private RedirectRoutesInfo $redirectRoutesInfo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redirectRoutesInfo = new RedirectRoutesInfo(
            'text.example.com/paypal/success',
            'text.example.com/paypal/failed'
        );
    }

    public function testCanCreatePaymentAndUpdatePaymentInfo()
    {
        $expectedApprovalUrl = 'https://paypal.com/payment/approve';
        $expectedPaymentId = 100;

        $this->expectTranslatorGetApiContext();

        $payment = $this->createPayment();
        $this->translator
            ->expects($this->once())
            ->method('getPayment')
            ->with($this->paymentInfo, $this->redirectRoutesInfo)
            ->willReturn($payment);

        $createdPayment = $this->createPaymentWithApprovedLink($expectedPaymentId, $expectedApprovalUrl);

        $this->client->expects($this->once())
            ->method('createPayment')
            ->with($payment, $this->apiContext)
            ->willReturn($createdPayment);

        $actualApprovalUrl = $this->transport
            ->setupPayment($this->paymentInfo, $this->apiContextInfo, $this->redirectRoutesInfo);
        $this->assertEquals($expectedApprovalUrl, $actualApprovalUrl);
        $this->assertEquals($expectedPaymentId, $this->paymentInfo->getPaymentId());
    }

    public function testThrowExceptionWhenClientCreatePaymentFails()
    {
        $clientException = new \Exception();

        $this->expectTranslatorGetApiContext();

        $expectedPayment = $this->createPayment();
        $this->translator
            ->expects($this->once())
            ->method('getPayment')
            ->with($this->paymentInfo, $this->redirectRoutesInfo)
            ->willReturn($expectedPayment);

        $this->client->expects($this->once())
            ->method('createPayment')
            ->willThrowException($clientException);

        $this->expectTransportException(
            'Create payment failed.',
            (new Context())->setPaymentInfo($this->paymentInfo)->setPayment($expectedPayment),
            $clientException
        );

        $this->transport->setupPayment($this->paymentInfo, $this->apiContextInfo, $this->redirectRoutesInfo);
    }

    public function testThrowExceptionWhenPaymentHasNotValidState()
    {
        $expectedPaymentId = 100;
        $expectedPaymentState = 'failed';
        $expectedFailureReason = 'Payment failed because of some error';

        $this->expectTranslatorGetApiContext();

        $payment = $this->createPayment();
        $this->translator
            ->expects($this->once())
            ->method('getPayment')
            ->with($this->paymentInfo, $this->redirectRoutesInfo)
            ->willReturn($payment);

        $failedPayment = $this->createPayment($expectedPaymentId, $expectedPaymentState, $expectedFailureReason);

        $this->client->expects($this->once())
            ->method('createPayment')
            ->with($payment, $this->apiContext)
            ->willReturn($failedPayment);

        $this->expectTransportException(
            'Unexpected state of payment after create.',
            (new Context())->setPaymentInfo($this->paymentInfo)->setPayment($failedPayment)
        );

        $this->transport->setupPayment($this->paymentInfo, $this->apiContextInfo, $this->redirectRoutesInfo);
        $this->assertEquals($expectedPaymentId, $this->paymentInfo->getPaymentId());
    }
}
