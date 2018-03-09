<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\DTO;

class ItemInfo
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var int
     */
    protected $quantity;

    /**
     * @var float
     */
    protected $price;

    /**
     * ItemInfo constructor.
     * @param string $name
     * @param string $currency
     * @param int    $quantity
     * @param float  $price
     */
    public function __construct(string $name, string $currency, int $quantity, float $price)
    {
        $this->name     = $name;
        $this->currency = $currency;
        $this->quantity = $quantity;
        $this->price    = $price;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }
}
