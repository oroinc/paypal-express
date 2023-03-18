<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Exception;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\Context;
use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\Payment;

class ContextTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider contextDataProvider
     */
    public function testGetContext(array $contextData, array $expected)
    {
        $context = new Context();

        $context->setPaymentInfo($contextData['paymentInfo']);
        $context->setPayment($contextData['payment']);
        $context->setAuthorization($contextData['authorization']);
        $context->setCapture($contextData['capture']);

        $actual = $context->getContext();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function contextDataProvider(): array
    {
        $paymentInfoPaymentId = 'ZxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $orderId = 'YxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentInfo = new PaymentInfo(0, 'USD', 0, 0, 0, '', '');
        $paymentInfo->setPaymentId($paymentInfoPaymentId);
        $paymentInfo->setOrderId($orderId);

        $paymentId = 'XxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $paymentState = 'failed';
        $paymentFailureReason = 'PAYER_CANNOT_PAY';
        $payment = new Payment();
        $payment->setId($paymentId);
        $payment->setState($paymentState);
        $payment->setFailureReason($paymentFailureReason);

        $authorizationState = 'expired';
        $authorizationReasonCode = 'AUTHORIZATION';
        $authorizationValidUntil = '2011-01-02';
        $authorization = new Authorization();
        $authorization->setState($authorizationState);
        $authorization->setReasonCode($authorizationReasonCode);
        $authorization->setValidUntil($authorizationValidUntil);

        $captureParentPayment = 'AxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';
        $captureState = 'pending';
        $capture = new Capture();
        $capture->setParentPayment($captureParentPayment);
        $capture->setState($captureState);

        return [
            'Get Context should return context in array format' => [
                'contextData' => [
                    'paymentInfo'   => $paymentInfo,
                    'payment'       => $payment,
                    'authorization' => $authorization,
                    'capture'       => $capture
                ],
                'expected'    => [
                    'payment_info'  => [
                        'payment_id' => $paymentInfoPaymentId,
                        'order_id'   => $orderId
                    ],
                    'payment'       => [
                        'id'             => $paymentId,
                        'state'          => $paymentState,
                        'failure_reason' => $paymentFailureReason
                    ],
                    'authorization' => [
                        'state'       => $authorizationState,
                        'reason_code' => $authorizationReasonCode,
                        'valid_until' => $authorizationValidUntil
                    ],
                    'capture'       => [
                        'parent_payment' => $captureParentPayment,
                        'state'          => $captureState,
                    ]
                ]
            ],
            'Get Context should work correctly if payment info are not set' => [
                'contextData' => [
                    'paymentInfo'   => null,
                    'payment'       => $payment,
                    'authorization' => $authorization,
                    'capture'       => $capture
                ],
                'expected'    => [
                    'payment'       => [
                        'id'             => $paymentId,
                        'state'          => $paymentState,
                        'failure_reason' => $paymentFailureReason
                    ],
                    'authorization' => [
                        'state'       => $authorizationState,
                        'reason_code' => $authorizationReasonCode,
                        'valid_until' => $authorizationValidUntil
                    ],
                    'capture'       => [
                        'parent_payment' => $captureParentPayment,
                        'state'          => $captureState,
                    ]
                ]
            ],
            'Get Context should work correctly if payment are not set' => [
                'contextData' => [
                    'paymentInfo'   => $paymentInfo,
                    'payment'       => null,
                    'authorization' => $authorization,
                    'capture'       => $capture
                ],
                'expected'    => [
                    'payment_info'  => [
                        'payment_id' => $paymentInfoPaymentId,
                        'order_id'   => $orderId
                    ],
                    'authorization' => [
                        'state'       => $authorizationState,
                        'reason_code' => $authorizationReasonCode,
                        'valid_until' => $authorizationValidUntil
                    ],
                    'capture'       => [
                        'parent_payment' => $captureParentPayment,
                        'state'          => $captureState,
                    ]
                ]
            ],
            'Get Context should work correctly if authorization are not set' => [
                'contextData' => [
                    'paymentInfo'   => $paymentInfo,
                    'payment'       => $payment,
                    'authorization' => null,
                    'capture'       => $capture
                ],
                'expected'    => [
                    'payment_info'  => [
                        'payment_id' => $paymentInfoPaymentId,
                        'order_id'   => $orderId
                    ],
                    'payment'       => [
                        'id'             => $paymentId,
                        'state'          => $paymentState,
                        'failure_reason' => $paymentFailureReason
                    ],
                    'capture'       => [
                        'parent_payment' => $captureParentPayment,
                        'state'          => $captureState,
                    ]
                ]
            ],
            'Get Context should work correctly if capture are not set' => [
                'contextData' => [
                    'paymentInfo'   => $paymentInfo,
                    'payment'       => $payment,
                    'authorization' => $authorization,
                    'capture'       => null
                ],
                'expected'    => [
                    'payment_info'  => [
                        'payment_id' => $paymentInfoPaymentId,
                        'order_id'   => $orderId
                    ],
                    'payment'       => [
                        'id'             => $paymentId,
                        'state'          => $paymentState,
                        'failure_reason' => $paymentFailureReason
                    ],
                    'authorization' => [
                        'state'       => $authorizationState,
                        'reason_code' => $authorizationReasonCode,
                        'valid_until' => $authorizationValidUntil
                    ]
                ]
            ],
        ];
    }
}
