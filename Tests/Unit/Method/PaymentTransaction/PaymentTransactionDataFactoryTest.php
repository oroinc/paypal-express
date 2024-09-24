<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentTransaction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfig;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeAndCaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionDataFactory;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionRequestData;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionResponseData;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;

class PaymentTransactionDataFactoryTest extends \PHPUnit\Framework\TestCase
{
    private PaymentTransactionDataFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new PaymentTransactionDataFactory();
    }

    public function testCreateResponseData()
    {
        $expectedPayerId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedPaymentId = 'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedOrderId = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedPaymentActionName = PaymentMethodInterface::CAPTURE;
        $expectedOnCompleteAction = AuthorizeAndCaptureAction::NAME;

        $transaction = new PaymentTransaction();
        $transaction->setAction($expectedPaymentActionName);

        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            '',
            $expectedOnCompleteAction,
            true
        );
        $paymentInfo = new PaymentInfo(
            1,
            'USD',
            0,
            0,
            0,
            PaymentInfo::PAYMENT_METHOD_PAYPAL,
            1
        );
        $paymentInfo->setOrderId($expectedOrderId);
        $paymentInfo->setPaymentId($expectedPaymentId);
        $paymentInfo->setPayerId($expectedPayerId);

        $actualResponse = $this->factory->createResponseData($transaction, $config, $paymentInfo);

        $expectedResponse = new PaymentTransactionResponseData();
        $expectedResponse->setPayerId($expectedPayerId);
        $expectedResponse->setPaymentId($expectedPaymentId);
        $expectedResponse->setOrderId($expectedOrderId);
        $expectedResponse->setPaymentActionConfig($expectedOnCompleteAction);
        $expectedResponse->setPaymentAction($expectedPaymentActionName);

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    public function testCreateResponseDataShouldWorkWithoutPaymentInfo()
    {
        $expectedPaymentId = 'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedPaymentActionName = PaymentMethodInterface::CAPTURE;
        $expectedOnCompleteAction = AuthorizeAndCaptureAction::NAME;

        $transaction = new PaymentTransaction();
        $transaction->setAction($expectedPaymentActionName);
        $transaction->setReference($expectedPaymentId);

        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            '',
            $expectedOnCompleteAction,
            true
        );

        $actualResponse = $this->factory->createResponseData($transaction, $config);

        $expectedResponse = new PaymentTransactionResponseData();
        $expectedResponse->setPaymentId($expectedPaymentId);
        $expectedResponse->setPaymentActionConfig($expectedOnCompleteAction);
        $expectedResponse->setPaymentAction($expectedPaymentActionName);

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    public function testCreateResponseDataFromArray()
    {
        $expectedPayerId = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedPaymentId = 'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedOrderId = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedPaymentActionName = PaymentMethodInterface::CAPTURE;
        $expectedOnCompleteAction = AuthorizeAndCaptureAction::NAME;

        $transaction = new PaymentTransaction();
        $transaction->setAction($expectedPaymentActionName);

        $expectedArray = [
            PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
            PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
            PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedOnCompleteAction,
            PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedPaymentActionName,
            PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
        ];

        $actualResponse = $this->factory->createResponseDataFromArray($expectedArray);

        $expectedResponse = new PaymentTransactionResponseData();
        $expectedResponse->setPayerId($expectedPayerId);
        $expectedResponse->setPaymentId($expectedPaymentId);
        $expectedResponse->setOrderId($expectedOrderId);
        $expectedResponse->setPaymentActionConfig($expectedOnCompleteAction);
        $expectedResponse->setPaymentAction($expectedPaymentActionName);

        $this->assertEquals($expectedResponse, $actualResponse);
    }

    public function testCreateRequestData()
    {
        $expectedPaymentId = 'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedPaymentActionName = PaymentMethodInterface::CAPTURE;
        $expectedOnCompleteAction = AuthorizeAndCaptureAction::NAME;
        $expectedCurrency = 'USD';
        $expectedTotal = '22.34';

        $transaction = new PaymentTransaction();
        $transaction->setAction($expectedPaymentActionName);
        $transaction->setReference($expectedPaymentId);
        $transaction->setCurrency($expectedCurrency);
        $transaction->setAmount($expectedTotal);

        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            '',
            $expectedOnCompleteAction,
            true
        );

        $actualRequest = $this->factory->createRequestData($transaction, $config);

        $expectedRequest = new PaymentTransactionRequestData();
        $expectedRequest->setPaymentId($expectedPaymentId);
        $expectedRequest->setPaymentActionConfig($expectedOnCompleteAction);
        $expectedRequest->setPaymentAction($expectedPaymentActionName);
        $expectedRequest->setCurrency($expectedCurrency);
        $expectedRequest->setTotalAmount($expectedTotal);

        $this->assertEquals($expectedRequest, $actualRequest);
    }

    public function testCreateRequestDataFromArray()
    {
        $expectedPaymentId = 'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedPaymentActionName = PaymentMethodInterface::CAPTURE;
        $expectedOnCompleteAction = AuthorizeAndCaptureAction::NAME;
        $expectedTotal = '22.34';
        $expectedCurrency = 'USD';

        $actualRequest = $this->factory->createRequestDataFromArray(
            [
                PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY => $expectedPaymentId,
                PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY => $expectedPaymentActionName,
                PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedOnCompleteAction,
                PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY => $expectedTotal,
                PaymentTransactionRequestData::CURRENCY_FIELD_KEY => $expectedCurrency,
            ]
        );

        $expectedRequest = new PaymentTransactionRequestData();
        $expectedRequest->setPaymentId($expectedPaymentId);
        $expectedRequest->setPaymentActionConfig($expectedOnCompleteAction);
        $expectedRequest->setPaymentAction($expectedPaymentActionName);
        $expectedRequest->setCurrency($expectedCurrency);
        $expectedRequest->setTotalAmount($expectedTotal);

        $this->assertEquals($expectedRequest, $actualRequest);
    }
}
