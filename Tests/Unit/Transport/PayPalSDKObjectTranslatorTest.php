<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ExceptionInfo;
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

class PayPalSDKObjectTranslatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PayPalSDKObjectTranslator
     */
    protected $payPalSDKObjectTranslator;

    protected function setUp()
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
            $items
        );

        $redirectRoutesInfo = new RedirectRoutesInfo($successRoute, $failedRoute);

        $actualPayment = $this->payPalSDKObjectTranslator->getPayment($paymentInfo, $redirectRoutesInfo);

        /** @var Transaction $transaction */
        $transaction = $actualPayment->getTransactions()[0];

        $invoiceNumber = $transaction->getInvoiceNumber();
        $this->assertNotEmpty($invoiceNumber);

        $itemList = new ItemList();
        $itemList->addItem($this->getItem($fooItemName, $currency, $fooQuantity, $fooPrice));
        $itemList->addItem($this->getItem($barItemName, $currency, $barQuantity, $barPrice));
        $expectedPayment = $this->getPayment(
            $itemList,
            $shipping,
            $tax,
            $subtotal,
            $currency,
            $totalAmount,
            $invoiceNumber,
            $successRoute,
            $failedRoute
        );

        $this->assertEquals($expectedPayment, $actualPayment);
    }

    protected function getItem($name, $currency, $quantity, $price)
    {
        $item = new Item();

        $item->setName($name);
        $item->setCurrency($currency);
        $item->setQuantity($quantity);
        $item->setPrice($price);

        return $item;
    }

    /**
     * @param ItemList $itemList
     * @param float    $shipping
     * @param float    $tax
     * @param float    $subtotal
     * @param string   $currency
     * @param float    $totalAmount
     * @param string   $invoiceNumber
     * @param string   $successRoute
     * @param string   $failedRoute
     *
     * @return Payment
     */
    protected function getPayment(
        ItemList $itemList,
        $shipping,
        $tax,
        $subtotal,
        $currency,
        $totalAmount,
        $invoiceNumber,
        $successRoute,
        $failedRoute
    ) {
        $payer           = new Payer();
        $payer->setPaymentMethod(PaymentInfo::PAYMENT_METHOD_PAYPAL);

        $details = new Details();
        $details->setShipping($shipping)
            ->setTax($tax)
            ->setSubtotal($subtotal);

        $amount = $this->getAmount($totalAmount, $currency);
        $amount->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setInvoiceNumber($invoiceNumber);

        $payment = new Payment();
        $payment->setIntent("order")
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
            PaymentInfo::PAYMENT_METHOD_PAYPAL
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
            PaymentInfo::PAYMENT_METHOD_PAYPAL
        );

        $expectedAmount = $this->getAmount($totalAmount, $currency);

        $captured = $this->payPalSDKObjectTranslator->getCapturedDetails($paymentInfo);
        $this->assertEquals($captured->getAmount(), $expectedAmount);
        $this->assertTrue($captured->getIsFinalCapture());
    }

    public function testGetExceptionInfo()
    {
        $message         = 'Order is already voided, expired, or completed.';
        $statusCode      = 'ORDER_ALREADY_COMPLETED';
        $details         = 'If the buyer\'s funding source has insufficient funds, restart the payment and ' .
            'prompt the buyer to choose another payment method that is available on your site.';
        $informationLink = 'https://developer.paypal.com/docs/api/payments/#errors';
        $debugId         = '2879152bf38c1';

        $url              = 'https://api.sandbox.paypal.com/v1/payments/orders/O-1DV55626YT3253642/capture';
        $exceptionMessage = 'Got Http response code 400 when accessing ' .
            'https://api.sandbox.paypal.com/v1/payments/orders/O-1DV55626YT3253642/capture.';
        $exception        = new PayPalConnectionException($url, $exceptionMessage);

        $rawData = json_encode(
            [
                'message'          => $message,
                'name'             => $statusCode,
                'details'          => $details,
                'information_link' => $informationLink,
                'debug_id'         => $debugId
            ]
        );
        $exception->setData($rawData);

        $paymentInfo = new PaymentInfo(0, 'USD', 0, 0, 0, '');

        $expectedExceptionInfo = new ExceptionInfo(
            $message,
            $statusCode,
            $details,
            $informationLink,
            $debugId,
            $paymentInfo,
            $rawData
        );

        $actualExceptionInfo = $this->payPalSDKObjectTranslator->getExceptionInfo($exception, $paymentInfo);

        $this->assertEquals($expectedExceptionInfo, $actualExceptionInfo);
    }

    public function testGetExceptionInfoShouldSetOriginalErrorForExceptionInfoIfDataIsNull()
    {
        $statusCode      = 400;

        $url              = 'https://api.sandbox.paypal.com/v1/payments/orders/O-1DV55626YT3253642/capture';
        $exceptionMessage = 'Got Http response code 400 when accessing ' .
            'https://api.sandbox.paypal.com/v1/payments/orders/O-1DV55626YT3253642/capture.';
        $exception        = new PayPalConnectionException($url, $exceptionMessage, $statusCode);

        $paymentInfo = new PaymentInfo(0, 'USD', 0, 0, 0, '');

        $expectedExceptionInfo = new ExceptionInfo(
            $exceptionMessage,
            $statusCode,
            '',
            '',
            '',
            $paymentInfo
        );

        $actualExceptionInfo = $this->payPalSDKObjectTranslator->getExceptionInfo($exception, $paymentInfo);

        $this->assertEquals($expectedExceptionInfo, $actualExceptionInfo);
    }

    public function testGetExceptionInfoShouldSetOriginalErrorForExceptionInfoIfDataCouldNotBeParsed()
    {
        $statusCode      = 400;

        $url              = 'https://api.sandbox.paypal.com/v1/payments/orders/O-1DV55626YT3253642/capture';
        $exceptionMessage = 'Got Http response code 400 when accessing ' .
            'https://api.sandbox.paypal.com/v1/payments/orders/O-1DV55626YT3253642/capture.';
        $exception        = new PayPalConnectionException($url, $exceptionMessage, $statusCode);
        $rawData = 'incorrect data';
        $exception->setData($rawData);

        $paymentInfo = new PaymentInfo(0, 'USD', 0, 0, 0, '');

        $expectedExceptionInfo = new ExceptionInfo(
            $exceptionMessage,
            $statusCode,
            '',
            '',
            '',
            $paymentInfo,
            $rawData
        );

        $actualExceptionInfo = $this->payPalSDKObjectTranslator->getExceptionInfo($exception, $paymentInfo);

        $this->assertEquals($expectedExceptionInfo, $actualExceptionInfo);
    }

    /**
     * @param float $amount
     * @param string $currency
     *
     * @return Amount
     */
    protected function getAmount($amount, $currency)
    {
        $expectedAmount = new Amount();
        $expectedAmount->setCurrency($currency);
        $expectedAmount->setTotal($amount);

        return $expectedAmount;
    }
}
