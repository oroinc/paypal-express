<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction;

/**
 * PayPal request data which will be saved in {@see PaymentTransaction::$request}.
 */
class PaymentTransactionRequestData
{
    const PAYMENT_ID_FIELD_KEY = 'paymentId';
    const PAYMENT_ACTION_FIELD_KEY = 'paymentAction';
    const PAYMENT_ACTION_CONFIG_FIELD_KEY = 'paymentActionConfig';
    const TOTAL_AMOUNT_FIELD_KEY = 'totalAmount';
    const CURRENCY_FIELD_KEY = 'currency';

    /**
     * @var string
     */
    protected $paymentId;

    /**
     * @var string
     */
    protected $paymentActionConfig;

    /**
     * @var string
     */
    protected $paymentAction;

    /**
     * string
     */
    protected $currency;

    /**
     * string
     */
    protected $totalAmount;

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
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param mixed $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * @param mixed $totalAmount
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    }

    public function toArray()
    {
        return [
            self::PAYMENT_ID_FIELD_KEY            => $this->paymentId,
            self::PAYMENT_ACTION_FIELD_KEY        => $this->paymentAction,
            self::PAYMENT_ACTION_CONFIG_FIELD_KEY => $this->paymentActionConfig,
            self::CURRENCY_FIELD_KEY              => $this->currency,
            self::TOTAL_AMOUNT_FIELD_KEY          => $this->totalAmount,
        ];
    }

    public function setFromArray(array $requestData)
    {
        if (isset($requestData[self::PAYMENT_ID_FIELD_KEY])) {
            $this->paymentId = $requestData[self::PAYMENT_ID_FIELD_KEY];
        }
        if (isset($requestData[self::PAYMENT_ACTION_FIELD_KEY])) {
            $this->paymentAction = $requestData[self::PAYMENT_ACTION_FIELD_KEY];
        }
        if (isset($requestData[self::PAYMENT_ACTION_CONFIG_FIELD_KEY])) {
            $this->paymentActionConfig = $requestData[self::PAYMENT_ACTION_CONFIG_FIELD_KEY];
        }
        if (isset($requestData[self::CURRENCY_FIELD_KEY])) {
            $this->currency = $requestData[self::CURRENCY_FIELD_KEY];
        }
        if (isset($requestData[self::TOTAL_AMOUNT_FIELD_KEY])) {
            $this->totalAmount = $requestData[self::TOTAL_AMOUNT_FIELD_KEY];
        }
    }
}
