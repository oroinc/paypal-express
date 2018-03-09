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
     * @param float      $totalAmount
     * @param string     $currency
     * @param float      $shipping
     * @param float      $tax
     * @param float      $subtotal
     * @param string     $method
     * @param ItemInfo[] $items
     */
    public function __construct(
        $totalAmount,
        $currency,
        $shipping,
        $tax,
        $subtotal,
        $method,
        array $items
    ) {
        $this->totalAmount = $totalAmount;
        $this->currency    = $currency;
        $this->shipping    = $shipping;
        $this->tax         = $tax;
        $this->subtotal    = $subtotal;
        $this->items       = $items;
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
}
