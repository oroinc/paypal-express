<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

/**
 * - Contains Currencies supported by PayPal
 * - Provide an access to restricted currencies
 * - Provide helper methods to check is currency: supported, not supported, supported but restricted
 */
class SupportedCurrenciesHelper
{
    /**
     * @var string[] ISO-4217
     */
    private $fullySupportedCurrencyCodes = [
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
    public function isSupportedCurrency($currencyCode)
    {
        $codes = $this->getSupportedCurrencyCodes();

        return in_array($currencyCode, $codes);
    }

    /**
     * @param string $currencyCode ISO-4217
     *
     * @return bool
     */
    public function isFullySupportedCurrency($currencyCode)
    {
        $codes = $this->getFullySupportedCurrencyCodes();

        return in_array($currencyCode, $codes);
    }

    /**
     * @param string $currencyCode ISO-4217
     *
     * @return bool
     */
    public function isCurrencyWithUnsupportedDecimals($currencyCode)
    {
        $codes = $this->getCurrencyCodesWhichIsNotSupportedDecimals();

        return in_array($currencyCode, $codes);
    }

    /**
     * @param string $currencyCode ISO-4217
     *
     * @return bool
     */
    public function isCurrencyCodeWhichIsSupportedOnlyForInCountryPayments($currencyCode)
    {
        $codes = $this->getCurrencyCodesWhichIsSupportedOnlyForInCountryPayments();

        return in_array($currencyCode, $codes);
    }

    /**
     * @return array
     */
    public function getSupportedCurrencyCodes()
    {
        return array_merge(
            $this->getFullySupportedCurrencyCodes(),
            $this->getCurrencyCodesWhichIsNotSupportedDecimals(),
            $this->getCurrencyCodesWhichIsSupportedOnlyForInCountryPayments()
        );
    }

    /**
     * @return array
     */
    public function getFullySupportedCurrencyCodes()
    {
        return $this->fullySupportedCurrencyCodes;
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
