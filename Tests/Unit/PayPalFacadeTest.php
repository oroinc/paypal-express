<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PaymentInfoTranslator;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalFacade;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalTransportInterface;

class PayPalFacadeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PayPalFacade
     */
    protected $facade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $payPalTransport;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentInfoTranslator;

    protected function setUp()
    {
        $this->payPalTransport = $this->createMock(PayPalTransportInterface::class);
        $this->paymentInfoTranslator = $this->createMock(PaymentInfoTranslator::class);

        $this->facade = new PayPalFacade($this->payPalTransport, $this->paymentInfoTranslator);
    }

    public function testGetPayPalPaymentRoute()
    {
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
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
        $credentialsInfo = new CredentialsInfo($clientId, $clientSecret);

        $paymentTransaction = new PaymentTransaction();

        $this->paymentInfoTranslator
            ->expects($this->once())
            ->method('getPaymentInfo')
            ->with($paymentTransaction)
            ->willReturn($paymentInfo);

        $this->payPalTransport
            ->expects($this->once())
            ->method('setupPayment')
            ->with($paymentInfo, $credentialsInfo, $successRoute, $failedRoute);

        $this->facade
            ->getPayPalPaymentRoute($paymentTransaction, $clientId, $clientSecret, $successRoute, $failedRoute);
    }
}
