<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentTransaction;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeAndCaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionRequestData;

class PaymentTransactionRequestDataTest extends \PHPUnit\Framework\TestCase
{
    public function testToArray()
    {
        $expectedPaymentId    = 'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedCurrency     = 'USD';
        $expectedActionConfig = AuthorizeAndCaptureAction::NAME;
        $expectedAction       = PaymentMethodInterface::CAPTURE;
        $expectedTotalAmount  = 22.34;
        $expectedArray = [
            PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
            PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => $expectedCurrency,
            PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
            PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
            PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => $expectedTotalAmount,
        ];

        $requestData = new PaymentTransactionRequestData();
        $requestData->setPaymentId($expectedPaymentId);
        $requestData->setTotalAmount($expectedTotalAmount);
        $requestData->setCurrency($expectedCurrency);
        $requestData->setPaymentAction($expectedAction);
        $requestData->setPaymentActionConfig($expectedActionConfig);

        $actualData = $requestData->toArray();
        $this->assertEquals($expectedArray, $actualData);
    }

    /**
     * @dataProvider setFromArrayDataProvider
     */
    public function testSetFromArray(array $data, array $expected)
    {
        $requestData = new PaymentTransactionRequestData();

        $requestData->setFromArray($data);

        $actualData = $requestData->toArray();
        $this->assertEquals($expected, $actualData);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function setFromArrayDataProvider()
    {
        $expectedPaymentId    = 'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedCurrency     = 'USD';
        $expectedActionConfig = AuthorizeAndCaptureAction::NAME;
        $expectedAction       = PaymentMethodInterface::CAPTURE;
        $expectedTotalAmount  = 22.34;

        return [
            'set from array should set all fields to object' => [
                'data' => [
                    PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => $expectedCurrency,
                    PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => $expectedTotalAmount,
                ],
                'expected' => [
                    PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => $expectedCurrency,
                    PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => $expectedTotalAmount,
                ],
            ],
            'set from array should ignore paymentId if it is not presented in array' => [
                'data' => [
                    PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => $expectedCurrency,
                    PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => $expectedTotalAmount,
                ],
                'expected' => [
                    PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => '',
                    PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => $expectedCurrency,
                    PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => $expectedTotalAmount,
                ],
            ],
            'set from array should ignore currency if it is not presented in array' => [
                'data' => [
                    PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => $expectedTotalAmount,
                ],
                'expected' => [
                    PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => '',
                    PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => $expectedTotalAmount,
                ],
            ],
            'set from array should ignore paymentActionConfig if it is not presented in array' => [
                'data' => [
                    PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => $expectedCurrency,
                    PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => $expectedTotalAmount,
                ],
                'expected' => [
                    PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => $expectedCurrency,
                    PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => '',
                    PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => $expectedTotalAmount,
                ],
            ],
            'set from array should ignore paymentAction if it is not presented in array' => [
                'data' => [
                    PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => $expectedCurrency,
                    PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => $expectedTotalAmount,
                ],
                'expected' => [
                    PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => $expectedCurrency,
                    PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => '',
                    PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => $expectedTotalAmount,
                ],
            ],
            'set from array should ignore totalAmount if it is not presented in array' => [
                'data' => [
                    PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => $expectedCurrency,
                    PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                ],
                'expected' => [
                    PaymentTransactionRequestData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionRequestData::CURRENCY_FIELD_KEY              => $expectedCurrency,
                    PaymentTransactionRequestData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionRequestData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionRequestData::TOTAL_AMOUNT_FIELD_KEY          => '',
                ],
            ],
        ];
    }
}
