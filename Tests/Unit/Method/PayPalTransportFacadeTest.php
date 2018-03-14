<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfig;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalTransportFacade;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\MethodConfigTranslator;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalTransportInterface;

class PayPalTransportFacadeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PayPalTransportFacade
     */
    protected $facade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PayPalTransportInterface
     */
    protected $payPalTransport;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PaymentTransactionTranslator
     */
    protected $paymentTransactionTranslator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|MethodConfigTranslator
     */
    protected $methodConfigTranslator;

    protected function setUp()
    {
        $this->payPalTransport              = $this->createMock(PayPalTransportInterface::class);
        $this->paymentTransactionTranslator = $this->createMock(PaymentTransactionTranslator::class);
        $this->methodConfigTranslator       = $this->createMock(MethodConfigTranslator::class);

        $this->facade = new PayPalTransportFacade(
            $this->payPalTransport,
            $this->paymentTransactionTranslator,
            $this->methodConfigTranslator
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
            []
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

        $this->payPalTransport
            ->expects($this->once())
            ->method('setupPayment')
            ->with($paymentInfo, $apiContextInfo, $redirectRoutesInfo)
            ->willReturn($expectedPaymentRoute);

        $config = new PayPalExpressConfig(
            'test',
            'test',
            'test',
            $clientId,
            $clientSecret,
            'test',
            $isSandbox
        );
        $this->methodConfigTranslator->expects($this->once())
            ->method('getApiContextInfo')
            ->with($config)
            ->willReturn($apiContextInfo);

        $actualPaymentRoute = $this->facade->getPayPalPaymentRoute($paymentTransaction, $config);
        $this->assertEquals($expectedPaymentRoute, $actualPaymentRoute);
    }

    public function testExecutePayPalPayment()
    {
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentId = 'GxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $payerId = 'HxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $isSandbox = false;

        $paymentInfo = new PaymentInfo(
            1.22,
            'USD',
            0.1,
            0.2,
            1.99,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            []
        );
        $apiContextInfo = new ApiContextInfo(new CredentialsInfo($clientId, $clientSecret), $isSandbox);

        $paymentTransaction = new PaymentTransaction();

        $this->paymentTransactionTranslator
            ->expects($this->once())
            ->method('getPaymentInfo')
            ->with($paymentTransaction, $paymentId, $payerId)
            ->willReturn($paymentInfo);

        $this->payPalTransport
            ->expects($this->once())
            ->method('executePayment')
            ->with($paymentInfo, $apiContextInfo);

        $config = new PayPalExpressConfig(
            'test',
            'test',
            'test',
            $clientId,
            $clientSecret,
            'test',
            $isSandbox
        );
        $this->methodConfigTranslator->expects($this->once())
            ->method('getApiContextInfo')
            ->with($config)
            ->willReturn($apiContextInfo);

        $this->facade->executePayPalPayment($paymentTransaction, $config, $paymentId, $payerId);
    }
}
