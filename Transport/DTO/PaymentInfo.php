<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\DTO;

class PaymentInfo
{
    const PAYMENT_METHOD_PAYPAL = 'paypal';

    /**
     * @var float
     */
    protected $totalAmount;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var float
     */
    protected $shipping;

    /**
     * @var float
     */
    protected $tax;

    /**
     * @var float
     */
    protected $subtotal;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var ItemInfo[]
     */
    protected $items = [];

    /**
     * @var string|null
     */
    protected $paymentId = null;

    /**
     * @var string|null
     */
    protected $payerId = null;

    /**
     * @param float      $totalAmount
     * @param string     $currency
     * @param float      $shipping
     * @param float      $tax
     * @param float      $subtotal
     * @param string     $method
     * @param ItemInfo[] $items
     * @param string     $paymentId
     * @param string     $payerId
     */
    public function __construct(
        $totalAmount,
        $currency,
        $shipping,
        $tax,
        $subtotal,
        $method,
        array $items = [],
        $paymentId = null,
        $payerId = null
    ) {
        $this->totalAmount = $totalAmount;
        $this->currency    = $currency;
        $this->shipping    = $shipping;
        $this->tax         = $tax;
        $this->subtotal    = $subtotal;
        $this->method      = $method;
        $this->items       = $items;
        $this->paymentId   = $paymentId;
        $this->payerId     = $payerId;
    }

    /**
     * @return float
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }


    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * @return float
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @return float
     */
    public function getSubtotal()
    {
        return $this->subtotal;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }


    /**
     * @return ItemInfo[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return null|string
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @return null|string
     */
    public function getPayerId()
    {
        return $this->payerId;
    }
}
