<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ErrorInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ItemInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalSDKObjectTranslator;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

class PayPalSDKObjectTranslatorTest extends \PHPUnit\Framework\TestCase
{
    private PayPalSDKObjectTranslator $payPalSDKObjectTranslator;

    protected function setUp(): void
    {
        $this->payPalSDKObjectTranslator = new PayPalSDKObjectTranslator();
    }

    public function testGetPayment()
    {
        $successRoute = 'http://text.example.com/paypal/success';
        $failedRoute = 'http://text.example.com/paypal/failed';
        $totalAmount = 22;
        $shipping = 2;
        $tax = 1;
        $subtotal = 19;
        $currency = 'USD';
        $invoiceNumber = 5;

        $fooItemName = 'foo item';
        $fooQuantity = 2;
        $fooPrice = 13;
        $barItemName = 'bar item';
        $barQuantity = 1;
        $barPrice = 6;

        $fooItem = new ItemInfo($fooItemName, $currency, $fooQuantity, $fooPrice);
        $barItem = new ItemInfo($barItemName, $currency, $barQuantity, $barPrice);

        $items = [
            $fooItem,
            $barItem
        ];

        $paymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            $shipping,
            $tax,
            $subtotal,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            $invoiceNumber,
            $items
        );

        $redirectRoutesInfo = new RedirectRoutesInfo($successRoute, $failedRoute);

        $actualPayment = $this->payPalSDKObjectTranslator->getPayment($paymentInfo, $redirectRoutesInfo);

        $itemList = new ItemList();
        $itemList->addItem($this->getItem($fooItemName, $currency, $fooQuantity, $fooPrice));
        $itemList->addItem($this->getItem($barItemName, $currency, $barQuantity, $barPrice));

        $details = new Details();
        $details->setShipping($shipping)
            ->setTax($tax)
            ->setSubtotal($subtotal);

        $expectedPayment = $this->getPayment(
            $details,
            $itemList,
            $currency,
            $totalAmount,
            $invoiceNumber,
            $successRoute,
            $failedRoute
        );

        $this->assertEquals($expectedPayment, $actualPayment);
    }

    public function testGetPaymentWithEmptyTax()
    {
        $successRoute = 'http://text.example.com/paypal/success';
        $failedRoute = 'http://text.example.com/paypal/failed';
        $totalAmount = 22;
        $shipping = 2;
        $tax = null;
        $subtotal = 19;
        $currency = 'USD';
        $invoiceNumber = 5;

        $fooItemName = 'foo item';
        $fooQuantity = 2;
        $fooPrice = 13;
        $barItemName = 'bar item';
        $barQuantity = 1;
        $barPrice = 6;

        $fooItem = new ItemInfo($fooItemName, $currency, $fooQuantity, $fooPrice);
        $barItem = new ItemInfo($barItemName, $currency, $barQuantity, $barPrice);

        $items = [
            $fooItem,
            $barItem
        ];

        $paymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            $shipping,
            $tax,
            $subtotal,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            $invoiceNumber,
            $items
        );

        $redirectRoutesInfo = new RedirectRoutesInfo($successRoute, $failedRoute);

        $actualPayment = $this->payPalSDKObjectTranslator->getPayment($paymentInfo, $redirectRoutesInfo);

        $itemList = new ItemList();
        $itemList->addItem($this->getItem($fooItemName, $currency, $fooQuantity, $fooPrice));
        $itemList->addItem($this->getItem($barItemName, $currency, $barQuantity, $barPrice));

        $details = new Details();
        $details->setShipping($shipping)
            ->setSubtotal($subtotal);

        $expectedPayment = $this->getPayment(
            $details,
            $itemList,
            $currency,
            $totalAmount,
            $invoiceNumber,
            $successRoute,
            $failedRoute
        );

        $this->assertEquals($expectedPayment, $actualPayment);
    }

    private function getItem(string $name, string $currency, int $quantity, float $price): Item
    {
        $item = new Item();

        $item->setName($name);
        $item->setCurrency($currency);
        $item->setQuantity($quantity);
        $item->setPrice($price);

        return $item;
    }

    private function getPayment(
        Details $details,
        ItemList $itemList,
        string $currency,
        float $totalAmount,
        string $invoiceNumber,
        string $successRoute,
        string $failedRoute
    ): Payment {
        $payer = new Payer();
        $payer->setPaymentMethod(PaymentInfo::PAYMENT_METHOD_PAYPAL);

        $amount = $this->getAmount($totalAmount, $currency);
        $amount->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setInvoiceNumber($invoiceNumber);

        $payment = new Payment();
        $payment->setIntent('order')
            ->setTransactions([$transaction])
            ->setPayer($payer);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($successRoute)
            ->setCancelUrl($failedRoute);

        $payment
            ->setRedirectUrls($redirectUrls);

        return $payment;
    }

    public function testGetApiContext()
    {
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedMod = PayPalSDKObjectTranslator::MOD_SANDBOX;
        $expectedApiContext = new ApiContext(new OAuthTokenCredential($clientId, $clientSecret));

        $contextInfo = new ApiContextInfo(new CredentialsInfo($clientId, $clientSecret), true);
        $actualAPIContext = $this->payPalSDKObjectTranslator->getApiContext($contextInfo);
        $this->assertEquals($expectedApiContext, $actualAPIContext);

        $actualMod = $actualAPIContext->get('mode');
        $this->assertEquals($expectedMod, $actualMod);
    }

    public function testGetApiContextWillReturnLiveApiContextIfIsSandboxFlagWillBeFalse()
    {
        $clientId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $clientSecret = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedMod = PayPalSDKObjectTranslator::MOD_LIVE;
        $expectedApiContext = new ApiContext(new OAuthTokenCredential($clientId, $clientSecret));

        $contextInfo = new ApiContextInfo(new CredentialsInfo($clientId, $clientSecret), false);
        $actualAPIContext = $this->payPalSDKObjectTranslator->getApiContext($contextInfo);
        $this->assertEquals($expectedApiContext, $actualAPIContext);

        $actualMod = $actualAPIContext->get('mode');
        $this->assertEquals($expectedMod, $actualMod);
    }

    public function testGetPaymentExecution()
    {
        $payerId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentInfo = new PaymentInfo(
            2,
            'USD',
            0.5,
            0.1,
            1.4,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            5,
            []
        );
        $paymentInfo->setPayerId($payerId);
        $paymentInfo->setPaymentId('BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ');
        $paymentExecution = $this->payPalSDKObjectTranslator->getPaymentExecution($paymentInfo);
        $this->assertEquals($payerId, $paymentExecution->getPayerId());
    }

    public function testGetAuthorization()
    {
        $totalAmount = 2;
        $currency = 'USD';
        $paymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            0.5,
            0.1,
            1.4,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            5
        );

        $expectedAmount = $this->getAmount($totalAmount, $currency);

        $authorization = $this->payPalSDKObjectTranslator->getAuthorization($paymentInfo);
        $this->assertEquals($authorization->getAmount(), $expectedAmount);
    }

    public function testGetCapturedDetails()
    {
        $totalAmount = 2;
        $currency = 'USD';
        $paymentInfo = new PaymentInfo(
            $totalAmount,
            $currency,
            0.5,
            0.1,
            1.4,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            5
        );

        $expectedAmount = $this->getAmount($totalAmount, $currency);

        $captured = $this->payPalSDKObjectTranslator->getCapturedDetails($paymentInfo);
        $this->assertEquals($captured->getAmount(), $expectedAmount);
        $this->assertTrue($captured->getIsFinalCapture());
    }

    /**
     * @dataProvider getErrorInfoDataProvider
     */
    public function testGetErrorInfo(array $data, ErrorInfo $errorInfo)
    {
        $exception = new PayPalConnectionException($data['url'], $data['message'], $data['code']);
        $exception->setData($data['data']);

        $actualErrorInfo = $this->payPalSDKObjectTranslator->getErrorInfo($exception);

        $this->assertEquals($errorInfo, $actualErrorInfo);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getErrorInfoDataProvider(): array
    {
        $message = 'Order is already voided, expired, or completed.';
        $statusCode = 'ORDER_ALREADY_COMPLETED';
        $details = 'If the buyer\'s funding source has insufficient funds, restart the payment and ' .
            'prompt the buyer to choose another payment method that is available on your site.';
        $informationLink = 'https://developer.paypal.com/docs/api/payments/#errors';
        $debugId = '2879152bf38c1';

        $url = 'https://api.sandbox.paypal.com/v1/payments/orders/O-1DV55626YT3253642/capture';
        $exceptionMessage = 'Got Http response code 400 when accessing ' .
            'https://api.sandbox.paypal.com/v1/payments/orders/O-1DV55626YT3253642/capture.';

        return [
            'should parse original SDK exception correctly'                                             => [
                'data'     => [
                    'url'     => $url,
                    'message' => $exceptionMessage,
                    'code'    => 400,
                    'data'    => json_encode([
                        'message'          => $message,
                        'name'             => $statusCode,
                        'details'          => $details,
                        'information_link' => $informationLink,
                        'debug_id'         => $debugId
                    ], JSON_THROW_ON_ERROR)
                ],
                'expected' => new ErrorInfo(
                    $message,
                    $statusCode,
                    $details,
                    $informationLink,
                    $debugId,
                    json_encode([
                        'message'          => $message,
                        'name'             => $statusCode,
                        'details'          => $details,
                        'information_link' => $informationLink,
                        'debug_id'         => $debugId
                    ], JSON_THROW_ON_ERROR)
                )
            ],
            'should set original exception message as message in case data is empty'                    => [
                'data'     => [
                    'url'     => $url,
                    'message' => $exceptionMessage,
                    'code'    => 400,
                    'data'    => null,
                ],
                'expected' => new ErrorInfo(
                    $exceptionMessage,
                    400,
                    '',
                    '',
                    '',
                    null
                )
            ],
            'should set original exception message as message in case data could not be parsed'         => [
                'data'     => [
                    'url'     => $url,
                    'message' => $exceptionMessage,
                    'code'    => 400,
                    'data'    => 'some incorrect response',
                ],
                'expected' => new ErrorInfo(
                    $exceptionMessage,
                    400,
                    '',
                    '',
                    '',
                    'some incorrect response'
                )
            ],
            'should set original exception message as message in case if data does not provide message' => [
                'data'     => [
                    'url'     => $url,
                    'message' => $exceptionMessage,
                    'code'    => 400,
                    'data'    => json_encode([
                        'name'             => $statusCode,
                        'details'          => $details,
                        'information_link' => $informationLink,
                        'debug_id'         => $debugId
                    ], JSON_THROW_ON_ERROR),
                ],
                'expected' => new ErrorInfo(
                    $exceptionMessage,
                    $statusCode,
                    $details,
                    $informationLink,
                    $debugId,
                    json_encode([
                        'name'             => $statusCode,
                        'details'          => $details,
                        'information_link' => $informationLink,
                        'debug_id'         => $debugId
                    ], JSON_THROW_ON_ERROR)
                )
            ],
            'should set original exception code as status code in case if data does not provide code'   => [
                'data'     => [
                    'url'     => $url,
                    'message' => $exceptionMessage,
                    'code'    => 400,
                    'data'    => json_encode([
                        'message'          => $message,
                        'details'          => $details,
                        'information_link' => $informationLink,
                        'debug_id'         => $debugId
                    ], JSON_THROW_ON_ERROR),
                ],
                'expected' => new ErrorInfo(
                    $message,
                    400,
                    $details,
                    $informationLink,
                    $debugId,
                    json_encode([
                        'message'          => $message,
                        'details'          => $details,
                        'information_link' => $informationLink,
                        'debug_id'         => $debugId
                    ], JSON_THROW_ON_ERROR)
                )
            ],
            'should work correctly if data does not provide details'                                    => [
                'data'     => [
                    'url'     => $url,
                    'message' => $exceptionMessage,
                    'code'    => 400,
                    'data'    => json_encode([
                        'message'          => $message,
                        'name'             => $statusCode,
                        'information_link' => $informationLink,
                        'debug_id'         => $debugId
                    ], JSON_THROW_ON_ERROR),
                ],
                'expected' => new ErrorInfo(
                    $message,
                    $statusCode,
                    '',
                    $informationLink,
                    $debugId,
                    json_encode([
                        'message'          => $message,
                        'name'             => $statusCode,
                        'information_link' => $informationLink,
                        'debug_id'         => $debugId
                    ], JSON_THROW_ON_ERROR)
                )
            ],
            'should work correctly if data does not provide information link'                           => [
                'data'     => [
                    'url'     => $url,
                    'message' => $exceptionMessage,
                    'code'    => 400,
                    'data'    => json_encode([
                        'message'  => $message,
                        'name'     => $statusCode,
                        'details'  => $details,
                        'debug_id' => $debugId
                    ], JSON_THROW_ON_ERROR),
                ],
                'expected' => new ErrorInfo(
                    $message,
                    $statusCode,
                    $details,
                    '',
                    $debugId,
                    json_encode([
                        'message'  => $message,
                        'name'     => $statusCode,
                        'details'  => $details,
                        'debug_id' => $debugId
                    ], JSON_THROW_ON_ERROR)
                )
            ],
            'should work correctly if data does not provide debug id'                                   => [
                'data'     => [
                    'url'     => $url,
                    'message' => $exceptionMessage,
                    'code'    => 400,
                    'data'    => json_encode([
                        'message'          => $message,
                        'name'             => $statusCode,
                        'details'          => $details,
                        'information_link' => $informationLink,
                    ], JSON_THROW_ON_ERROR),
                ],
                'expected' => new ErrorInfo(
                    $message,
                    $statusCode,
                    $details,
                    $informationLink,
                    '',
                    json_encode([
                        'message'          => $message,
                        'name'             => $statusCode,
                        'details'          => $details,
                        'information_link' => $informationLink,
                    ], JSON_THROW_ON_ERROR)
                )
            ]
        ];
    }

    private function getAmount(float $amount, string $currency): Amount
    {
        $expectedAmount = new Amount();
        $expectedAmount->setCurrency($currency);
        $expectedAmount->setTotal($amount);

        return $expectedAmount;
    }
}
