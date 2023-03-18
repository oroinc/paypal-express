<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfig;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeAndCaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CompleteVirtualAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionDataFactory;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionRequestData;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionResponseData;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalExpressTransportFacade;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\MethodConfigTranslator;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalExpressTransportInterface;

class PayPalExpressTransportFacadeTest extends \PHPUnit\Framework\TestCase
{
    /** @var PayPalExpressTransportFacade */
    private $facade;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PayPalExpressTransportInterface */
    private $payPalExpressTransport;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PaymentTransactionTranslator */
    private $paymentTransactionTranslator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MethodConfigTranslator */
    private $methodConfigTranslator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PaymentTransactionDataFactory */
    private $paymentTransactionDataFactory;

    protected function setUp(): void
    {
        $this->payPalExpressTransport = $this->createMock(PayPalExpressTransportInterface::class);
        $this->paymentTransactionTranslator = $this->createMock(PaymentTransactionTranslator::class);
        $this->methodConfigTranslator = $this->createMock(MethodConfigTranslator::class);
        $this->paymentTransactionDataFactory = $this->createMock(PaymentTransactionDataFactory::class);

        $this->facade = new PayPalExpressTransportFacade(
            $this->payPalExpressTransport,
            $this->paymentTransactionTranslator,
            $this->methodConfigTranslator,
            $this->paymentTransactionDataFactory
        );
    }

    public function testGetPayPalPaymentRoute()
    {
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $isSandbox = false;
        $successRoute = 'text.example.com/paypal/success';
        $failedRoute = 'text.example.com/paypal/failed';

        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            12
        );
        $apiContextInfo = new ApiContextInfo(new CredentialsInfo($clientId, $clientSecret), $isSandbox);

        $redirectRoutesInfo = new RedirectRoutesInfo($successRoute, $failedRoute);

        $paymentTransaction = new PaymentTransaction();

        $expectedPaymentRoute = 'https://paypal.com/payment/approve';
        $this->paymentTransactionTranslator
            ->expects($this->once())
            ->method('getPaymentInfo')
            ->with($paymentTransaction)
            ->willReturn($paymentInfo);
        $this->paymentTransactionTranslator
            ->expects($this->once())
            ->method('getRedirectRoutes')
            ->with($paymentTransaction)
            ->willReturn($redirectRoutesInfo);

        $expectedPaymentInfo = clone $paymentInfo;
        $this->payPalExpressTransport
            ->expects($this->once())
            ->method('setupPayment')
            ->with($expectedPaymentInfo, $apiContextInfo, $redirectRoutesInfo)
            ->willReturn($expectedPaymentRoute);

        $config = new PayPalExpressConfig(
            'test',
            'test',
            'test',
            $clientId,
            $clientSecret,
            'test',
            AuthorizeAndCaptureAction::NAME,
            $isSandbox
        );
        $this->methodConfigTranslator->expects($this->once())
            ->method('getApiContextInfo')
            ->with($config)
            ->willReturn($apiContextInfo);

        $expectedRequestData = [
            'paymentId'           => 'LxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'paymentAction'       => PaymentMethodInterface::PURCHASE,
            'paymentActionConfig' => 'authorize',
            'currency'            => 'USD',
            'totalAmount'         => 21,
        ];
        $this->paymentTransactionDataFactory->expects($this->once())
            ->method('createRequestData')
            ->with($paymentTransaction, $config)
            ->willReturn($this->createPaymentTransactionRequestFromData($expectedRequestData));

        $expectedResponseData = [
            'paymentId'           => 'LxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'orderId'             => 'HxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'paymentAction'       => PaymentMethodInterface::PURCHASE,
            'paymentActionConfig' => 'authorize',
            'payerId'             => 'XxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
        ];
        $this->paymentTransactionDataFactory->expects($this->once())
            ->method('createResponseData')
            ->with($paymentTransaction, $config, $expectedPaymentInfo)
            ->willReturn($this->createPaymentTransactionResponseFromData($expectedResponseData));

        $actualPaymentRoute = $this->facade->getPayPalPaymentRoute($paymentTransaction, $config);
        $this->assertEquals($expectedPaymentRoute, $actualPaymentRoute);

        $this->assertEquals($expectedRequestData, $paymentTransaction->getRequest());

        $this->assertEquals($expectedResponseData, $paymentTransaction->getResponse());
    }

    public function testExecutePayPalPayment()
    {
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentId = 'GxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $payerId = 'HxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $isSandbox = false;

        $apiContextInfo = new ApiContextInfo(new CredentialsInfo($clientId, $clientSecret), $isSandbox);

        $paymentTransaction = new PaymentTransaction();

        $createdTransactionResponseData = [
            'paymentId'           => $paymentId,
            'orderId'             => null,
            'paymentAction'       => PaymentMethodInterface::PURCHASE,
            'paymentActionConfig' => 'authorize_and_capture',
            'payerId'             => $payerId,
        ];
        $this->paymentTransactionDataFactory->expects($this->once())
            ->method('createResponseDataFromArray')
            ->with($createdTransactionResponseData)
            ->willReturn($this->createPaymentTransactionResponseFromData($createdTransactionResponseData));

        $paymentTransaction->setResponse($createdTransactionResponseData);

        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            12
        );
        $this->paymentTransactionTranslator
            ->expects($this->once())
            ->method('getPaymentInfo')
            ->with($paymentTransaction)
            ->willReturn($paymentInfo);

        $expectedPaymentInfo = clone $paymentInfo;
        $expectedPaymentInfo->setPaymentId($paymentId);
        $expectedPaymentInfo->setPayerId($payerId);
        $this->payPalExpressTransport
            ->expects($this->once())
            ->method('executePayment')
            ->with($expectedPaymentInfo, $apiContextInfo);

        $config = new PayPalExpressConfig(
            'test',
            'test',
            'test',
            $clientId,
            $clientSecret,
            'test',
            AuthorizeAndCaptureAction::NAME,
            $isSandbox
        );
        $this->methodConfigTranslator->expects($this->once())
            ->method('getApiContextInfo')
            ->with($config)
            ->willReturn($apiContextInfo);

        $expectedRequestData = [
            'paymentId'           => $paymentId,
            'paymentAction'       => CompleteVirtualAction::NAME,
            'paymentActionConfig' => 'authorize',
            'currency'            => 'USD',
            'totalAmount'         => 21,
        ];
        $this->paymentTransactionDataFactory->expects($this->once())
            ->method('createRequestData')
            ->with($paymentTransaction, $config)
            ->willReturn($this->createPaymentTransactionRequestFromData($expectedRequestData));

        $expectedResponseData = [
            'paymentId'           => $paymentId,
            'orderId'             => 'HxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
            'paymentAction'       => CompleteVirtualAction::NAME,
            'paymentActionConfig' => 'authorize_and_capture',
            'payerId'             => $payerId,
        ];
        $this->paymentTransactionDataFactory->expects($this->once())
            ->method('createResponseData')
            ->with($paymentTransaction, $config, $expectedPaymentInfo)
            ->willReturn($this->createPaymentTransactionResponseFromData($expectedResponseData));

        $this->facade->executePayPalPayment($paymentTransaction, $config);

        $this->assertEquals($expectedRequestData, $paymentTransaction->getRequest());

        $this->assertEquals($expectedResponseData, $paymentTransaction->getResponse());
    }

    public function testCapturePayment()
    {
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentId = 'GxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $payerId = 'HxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $orderId = 'ZxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $isSandbox = false;

        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            12
        );
        $apiContextInfo = new ApiContextInfo(new CredentialsInfo($clientId, $clientSecret), $isSandbox);

        $authorizedPaymentTransaction = new PaymentTransaction();
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setSourcePaymentTransaction($authorizedPaymentTransaction);

        $createdTransactionResponseData = [
            'paymentId'           => $paymentId,
            'orderId'             => $orderId,
            'paymentAction'       => CompleteVirtualAction::NAME,
            'paymentActionConfig' => 'authorize',
            'payerId'             => $payerId,
        ];
        $this->paymentTransactionDataFactory->expects($this->once())
            ->method('createResponseDataFromArray')
            ->with($createdTransactionResponseData)
            ->willReturn($this->createPaymentTransactionResponseFromData($createdTransactionResponseData));

        $authorizedPaymentTransaction->setResponse($createdTransactionResponseData);

        $this->paymentTransactionTranslator
            ->expects($this->once())
            ->method('getPaymentInfo')
            ->with($paymentTransaction)
            ->willReturn($paymentInfo);

        $expectedPaymentInfo = clone $paymentInfo;
        $expectedPaymentInfo->setPayerId($payerId);
        $expectedPaymentInfo->setPaymentId($paymentId);
        $expectedPaymentInfo->setOrderId($orderId);
        $this->payPalExpressTransport
            ->expects($this->once())
            ->method('capturePayment')
            ->with($expectedPaymentInfo, $apiContextInfo);

        $config = new PayPalExpressConfig(
            'test',
            'test',
            'test',
            $clientId,
            $clientSecret,
            'test',
            AuthorizeAndCaptureAction::NAME,
            $isSandbox
        );
        $this->methodConfigTranslator->expects($this->once())
            ->method('getApiContextInfo')
            ->with($config)
            ->willReturn($apiContextInfo);

        $expectedRequestData = [
            'paymentId'           => $paymentId,
            'paymentAction'       => PaymentMethodInterface::CAPTURE,
            'paymentActionConfig' => 'authorize',
            'currency'            => 'USD',
            'totalAmount'         => 21,
        ];
        $this->paymentTransactionDataFactory->expects($this->once())
            ->method('createRequestData')
            ->with($paymentTransaction, $config)
            ->willReturn($this->createPaymentTransactionRequestFromData($expectedRequestData));

        $expectedResponseData = [
            'paymentId'           => $paymentId,
            'orderId'             => $orderId,
            'paymentAction'       => PaymentMethodInterface::CAPTURE,
            'paymentActionConfig' => 'authorize',
            'payerId'             => $payerId,
        ];
        $this->paymentTransactionDataFactory->expects($this->once())
            ->method('createResponseData')
            ->with($paymentTransaction, $config, $expectedPaymentInfo)
            ->willReturn($this->createPaymentTransactionResponseFromData($expectedResponseData));

        $this->facade->capturePayment($paymentTransaction, $authorizedPaymentTransaction, $config);

        $this->assertEquals($expectedRequestData, $paymentTransaction->getRequest());

        $this->assertEquals($expectedResponseData, $paymentTransaction->getResponse());
    }

    public function testAuthorizePayment()
    {
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentId = 'GxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $payerId = 'HxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $orderId = 'ZxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $isSandbox = false;

        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            12
        );
        $apiContextInfo = new ApiContextInfo(new CredentialsInfo($clientId, $clientSecret), $isSandbox);

        $paymentTransaction = new PaymentTransaction();

        $createdTransactionResponseData = [
            'paymentId'           => $paymentId,
            'orderId'             => $orderId,
            'paymentAction'       => CompleteVirtualAction::NAME,
            'paymentActionConfig' => 'authorize',
            'payerId'             => $payerId,
        ];
        $this->paymentTransactionDataFactory->expects($this->once())
            ->method('createResponseDataFromArray')
            ->with($createdTransactionResponseData)
            ->willReturn($this->createPaymentTransactionResponseFromData($createdTransactionResponseData));

        $paymentTransaction->setResponse($createdTransactionResponseData);

        $this->paymentTransactionTranslator
            ->expects($this->once())
            ->method('getPaymentInfo')
            ->with($paymentTransaction)
            ->willReturn($paymentInfo);

        $expectedPaymentInfo = clone $paymentInfo;
        $expectedPaymentInfo->setPayerId($payerId);
        $expectedPaymentInfo->setPaymentId($paymentId);
        $expectedPaymentInfo->setOrderId($orderId);
        $this->payPalExpressTransport
            ->expects($this->once())
            ->method('authorizePayment')
            ->with($expectedPaymentInfo, $apiContextInfo);

        $config = new PayPalExpressConfig(
            'test',
            'test',
            'test',
            $clientId,
            $clientSecret,
            'test',
            AuthorizeAndCaptureAction::NAME,
            $isSandbox
        );
        $this->methodConfigTranslator->expects($this->once())
            ->method('getApiContextInfo')
            ->with($config)
            ->willReturn($apiContextInfo);

        $expectedRequestData = [
            'paymentId'           => $paymentId,
            'paymentAction'       => PaymentMethodInterface::CAPTURE,
            'paymentActionConfig' => 'authorize',
            'currency'            => 'USD',
            'totalAmount'         => 21,
        ];
        $this->paymentTransactionDataFactory->expects($this->once())
            ->method('createRequestData')
            ->with($paymentTransaction, $config)
            ->willReturn($this->createPaymentTransactionRequestFromData($expectedRequestData));

        $expectedResponseData = [
            'paymentId'           => $paymentId,
            'orderId'             => $orderId,
            'paymentAction'       => PaymentMethodInterface::CAPTURE,
            'paymentActionConfig' => 'authorize',
            'payerId'             => $payerId,
        ];
        $this->paymentTransactionDataFactory->expects($this->once())
            ->method('createResponseData')
            ->with($paymentTransaction, $config, $expectedPaymentInfo)
            ->willReturn($this->createPaymentTransactionResponseFromData($expectedResponseData));

        $this->facade->authorizePayment($paymentTransaction, $config);

        $this->assertEquals($expectedRequestData, $paymentTransaction->getRequest());

        $this->assertEquals($expectedResponseData, $paymentTransaction->getResponse());
    }

    private function createPaymentTransactionRequestFromData(array $data): PaymentTransactionRequestData
    {
        $response = new PaymentTransactionRequestData();
        $response->setFromArray($data);

        return $response;
    }

    private function createPaymentTransactionResponseFromData(array $data): PaymentTransactionResponseData
    {
        $response = new PaymentTransactionResponseData();
        $response->setFromArray($data);

        return $response;
    }
}
