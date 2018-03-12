<?php

namespace Oro\Bundle\PayPalExpressBundle\Exception;

class UnsupportedCurrencyException extends UnsupportedValueException
{
    /**
     * @param                 $invalidCurrency
     * @param array           $listOfSupportedCurrencies
     * @param \Throwable|null $previous
     *
     * @return UnsupportedCurrencyException
     */
    public static function create($invalidCurrency, $listOfSupportedCurrencies = [], \Throwable $previous = null)
    {
        $message = sprintf('Currency "%s" is not supported.', $invalidCurrency);

        if ($listOfSupportedCurrencies) {
            $message .= sprintf(' Only next currencies are supported: "%s"', implode($listOfSupportedCurrencies));
        }

        return new UnsupportedCurrencyException(
            $message,
            0,
            $previous
        );
    }
}
