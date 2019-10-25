<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentTransaction;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeAndCaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionResponseData;

class PaymentTransactionResponseDataTest extends \PHPUnit\Framework\TestCase
{
    public function testToArray()
    {
        $expectedPaymentId    = 'BxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedOrderId      = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedPayerId      = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedActionConfig = AuthorizeAndCaptureAction::NAME;
        $expectedAction       = PaymentMethodInterface::CAPTURE;

        $expectedArray = [
            PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
            PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
            PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
            PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
            PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
        ];

        $requestData = new PaymentTransactionResponseData();
        $requestData->setPaymentId($expectedPaymentId);
        $requestData->setOrderId($expectedOrderId);
        $requestData->setPayerId($expectedPayerId);
        $requestData->setPaymentAction($expectedAction);
        $requestData->setPaymentActionConfig($expectedActionConfig);

        $actualData = $requestData->toArray();
        $this->assertEquals($expectedArray, $actualData);
    }

    /**
     * @dataProvider setFromArrayDataProvider
     *
     * @param array $data
     * @param array $expected
     */
    public function testSetFromArray(array $data, array $expected)
    {
        $requestData = new PaymentTransactionResponseData();

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
        $expectedOrderId      = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedPayerId      = 'CxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $expectedActionConfig = AuthorizeAndCaptureAction::NAME;
        $expectedAction       = PaymentMethodInterface::CAPTURE;

        return [
            'set from array should set all fields to object' => [
                'data' => [
                    PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
                    PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
                ],
                'expected' => [
                    PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
                    PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
                ],
            ],
            'set from array should ignore paymentId if it is not presented in array' => [
                'data' => [
                    PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
                    PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
                ],
                'expected' => [
                    PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => '',
                    PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
                    PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
                ],
            ],
            'set from array should ignore orderId if it is not presented in array' => [
                'data' => [
                    PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
                ],
                'expected' => [
                    PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => '',
                    PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
                ],
            ],
            'set from array should ignore paymentActionConfig if it is not presented in array' => [
                'data' => [
                    PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
                    PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
                ],
                'expected' => [
                    PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
                    PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => '',
                    PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
                ],
            ],
            'set from array should ignore paymentAction if it is not presented in array' => [
                'data' => [
                    PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
                    PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
                ],
                'expected' => [
                    PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
                    PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => '',
                    PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => $expectedPayerId,
                ],
            ],
            'set from array should ignore payerId if it is not presented in array' => [
                'data' => [
                    PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
                    PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                ],
                'expected' => [
                    PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY            => $expectedPaymentId,
                    PaymentTransactionResponseData::ORDER_ID_FIELD_KEY              => $expectedOrderId,
                    PaymentTransactionResponseData::PAYMENT_ACTION_CONFIG_FIELD_KEY => $expectedActionConfig,
                    PaymentTransactionResponseData::PAYMENT_ACTION_FIELD_KEY        => $expectedAction,
                    PaymentTransactionResponseData::PAYER_ID_FIELD_KEY              => '',
                ],
            ],
        ];
    }
}
