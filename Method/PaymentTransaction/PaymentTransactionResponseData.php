<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction;

class PaymentTransactionResponseData
{
    const PAYMENT_ID_FIELD_KEY = 'paymentId';
    const ORDER_ID_FIELD_KEY = 'orderId';
    const PAYMENT_ACTION_FIELD_KEY = 'paymentAction';
    const PAYMENT_ACTION_CONFIG_FIELD_KEY = 'paymentActionConfig';
    const DATA_FIELD_KEY = 'data';

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
     * @var array
     */
    protected $data = [];

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
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function toArray()
    {
        return [
            self::PAYMENT_ID_FIELD_KEY            => $this->paymentId,
            self::ORDER_ID_FIELD_KEY              => $this->orderId,
            self::PAYMENT_ACTION_FIELD_KEY        => $this->paymentAction,
            self::PAYMENT_ACTION_CONFIG_FIELD_KEY => $this->paymentActionConfig,
            self::DATA_FIELD_KEY                  => $this->data,
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
        if (isset($responseData[self::DATA_FIELD_KEY])) {
            $this->data = $responseData[self::DATA_FIELD_KEY];
        }
    }
}
