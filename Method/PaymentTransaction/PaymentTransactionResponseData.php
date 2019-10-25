<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction;

/**
 * PayPal respone data which will be saved in {@see PaymentTransaction::$respone}.
 */
class PaymentTransactionResponseData
{
    const PAYMENT_ID_FIELD_KEY = 'paymentId';
    const ORDER_ID_FIELD_KEY = 'orderId';
    const PAYMENT_ACTION_FIELD_KEY = 'paymentAction';
    const PAYMENT_ACTION_CONFIG_FIELD_KEY = 'paymentActionConfig';
    const PAYER_ID_FIELD_KEY = 'payerId';

    /**
     * @var string
     */
    protected $paymentId;

    /**
     * @var string
     */
    protected $orderId;

    /**
     * @var string
     */
    protected $paymentActionConfig;

    /**
     * @var string
     */
    protected $paymentAction;

    /**
     * @var string
     */
    protected $payerId;

    /**
     * @return string
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @param string $paymentId
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @return string
     */
    public function getPaymentActionConfig()
    {
        return $this->paymentActionConfig;
    }

    /**
     * @param string $paymentActionConfig
     */
    public function setPaymentActionConfig($paymentActionConfig)
    {
        $this->paymentActionConfig = $paymentActionConfig;
    }

    /**
     * @return string
     */
    public function getPaymentAction()
    {
        return $this->paymentAction;
    }

    /**
     * @param string $paymentAction
     */
    public function setPaymentAction($paymentAction)
    {
        $this->paymentAction = $paymentAction;
    }

    /**
     * @return string
     */
    public function getPayerId()
    {
        return $this->payerId;
    }

    /**
     * @param string $payerId
     */
    public function setPayerId($payerId)
    {
        $this->payerId = $payerId;
    }

    public function toArray()
    {
        return [
            self::PAYMENT_ID_FIELD_KEY            => $this->paymentId,
            self::ORDER_ID_FIELD_KEY              => $this->orderId,
            self::PAYMENT_ACTION_FIELD_KEY        => $this->paymentAction,
            self::PAYMENT_ACTION_CONFIG_FIELD_KEY => $this->paymentActionConfig,
            self::PAYER_ID_FIELD_KEY              => $this->payerId,
        ];
    }

    public function setFromArray(array $responseData)
    {
        if (isset($responseData[self::PAYMENT_ID_FIELD_KEY])) {
            $this->paymentId = $responseData[self::PAYMENT_ID_FIELD_KEY];
        }
        if (isset($responseData[self::ORDER_ID_FIELD_KEY])) {
            $this->orderId = $responseData[self::ORDER_ID_FIELD_KEY];
        }
        if (isset($responseData[self::PAYMENT_ACTION_FIELD_KEY])) {
            $this->paymentAction = $responseData[self::PAYMENT_ACTION_FIELD_KEY];
        }
        if (isset($responseData[self::PAYMENT_ACTION_CONFIG_FIELD_KEY])) {
            $this->paymentActionConfig = $responseData[self::PAYMENT_ACTION_CONFIG_FIELD_KEY];
        }
        if (isset($responseData[self::PAYER_ID_FIELD_KEY])) {
            $this->payerId = $responseData[self::PAYER_ID_FIELD_KEY];
        }
    }
}
