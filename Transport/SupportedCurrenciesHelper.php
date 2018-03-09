<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

class SupportedCurrenciesHelper
{
    /**
     * @var string[] ISO-4217
     */
    private $currencyCodes = [
        'AUD',
        'CAD',
        'CZK',
        'DKK',
        'EUR',
        'HKD',
        'ILS',
        'MXN',
        'NZD',
        'NOK',
        'PHP',
        'PLN',
        'GBP',
        'RUB',
        'SGD',
        'SEK',
        'CHF',
        'THB',
        'USD',
    ];

    /**
     * @var string[] ISO-4217
     */
    private $currencyCodesWhichIsNotSupportDecimal = [
        'HUF',
        'JPY',
        'TWD',
    ];

    /**
     * @var string[] ISO-4217
     */
    private $currencyCodesWhichIsSupportedOnlyForInCountryPayments = [
        'BRL',
        'MYR'
    ];

    /**
     * @param string $currencyCode ISO-4217
     *
     * @return bool
     */
    public function isFullySupporterdCurrency($currencyCode)
    {
        $codes = $this->getFullySupportedCurrencyCodes();

        return in_array($currencyCode, $codes);
    }

    /**
     * @return array
     */
    public function getFullySupportedCurrencyCodes()
    {
        return $this->currencyCodes;
    }

    /**
     * @return array
     */
    public function getCurrencyCodesWhichIsNotSupportedDecimals()
    {
        return $this->currencyCodesWhichIsNotSupportDecimal;
    }

    /**
     * @return array
     */
    public function getCurrencyCodesWhichIsSupportedOnlyForInCountryPayments()
    {
        return $this->currencyCodesWhichIsSupportedOnlyForInCountryPayments;
    }
}
