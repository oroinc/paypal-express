<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\DTO;

/**
 * Represents information about PayPal REST API payment.
 */
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
     * @var string
     */
    protected $invoiceNumber;

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
     * @var null|string
     */
    protected $orderId = null;

    /**
     * @param float      $totalAmount
     * @param string     $currency
     * @param float      $shipping
     * @param null|float $tax
     * @param float      $subtotal
     * @param string     $method
     * @param string     $invoiceNumber
     * @param ItemInfo[] $items
     */
    public function __construct(
        $totalAmount,
        $currency,
        $shipping,
        $tax,
        $subtotal,
        $method,
        $invoiceNumber,
        array $items = []
    ) {
        $this->totalAmount   = $totalAmount;
        $this->currency      = $currency;
        $this->shipping      = $shipping;
        $this->tax           = $tax;
        $this->subtotal      = $subtotal;
        $this->method        = $method;
        $this->invoiceNumber = $invoiceNumber;
        $this->items         = $items;
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
     * @return null|float
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
     * @return string
     */
    public function getInvoiceNumber()
    {
        return $this->invoiceNumber;
    }

    /**
     * @param string $invoiceNumber
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        $this->invoiceNumber = $invoiceNumber;
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
     * @param null|string $paymentId
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * @return null|string
     */
    public function getPayerId()
    {
        return $this->payerId;
    }

    /**
     * @param null|string $payerId
     */
    public function setPayerId($payerId)
    {
        $this->payerId = $payerId;
    }

    /**
     * @return null|string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param null|string $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }
}
