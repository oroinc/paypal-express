<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\PayPalClient;
use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\Order;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Rest\ApiContext;

class PayPalClientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PayPalClient
     */
    protected $client;

    protected function setUp(): void
    {
        $this->client = new PayPalClient();
    }

    public function testCreatePayment()
    {
        $payment = $this->createMock(Payment::class);
        $apiContext = new ApiContext();

        $payment->expects($this->once())
            ->method('create')
            ->with($apiContext);

        $this->client->createPayment($payment, $apiContext);
    }

    public function testExecutePayment()
    {
        $payment = $this->createMock(Payment::class);
        $execution = new PaymentExecution();
        $apiContext = new ApiContext();

        $payment->expects($this->once())
            ->method('execute')
            ->with($execution, $apiContext);

        $this->client->executePayment($payment, $execution, $apiContext);
    }

    public function testAuthorizeOrder()
    {
        $order = $this->createMock(Order::class);
        $authorization = new Authorization();
        $apiContext = new ApiContext();

        $order->expects($this->once())
            ->method('authorize')
            ->with($authorization, $apiContext);

        $this->client->authorizeOrder($order, $authorization, $apiContext);
    }

    public function testCaptureOrder()
    {
        $order = $this->createMock(Order::class);
        $capture = new Capture();
        $apiContext = new ApiContext();

        $order->expects($this->once())
            ->method('capture')
            ->with($capture, $apiContext);

        $this->client->captureOrder($order, $capture, $apiContext);
    }
}
