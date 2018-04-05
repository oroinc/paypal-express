<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

/**
 * Contains currencies supported by PayPal and has next responsibilities:
 * - Provide an access to restricted currencies.
 * - Provide helper methods to check is currency: supported, not supported, supported but restricted.
 *
 * @link https://developer.paypal.com/docs/classic/mass-pay/integration-guide/currency_codes/
 */
class SupportedCurrenciesHelper
{
    /**
     * @var string[] Currencies codes in ISO-4217
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
     * @var string[] Currencies codes in ISO-4217
     */
    private $currencyCodesWhichIsNotSupportDecimal = [
        'HUF',
        'JPY',
        'TWD',
    ];

    /**
     * @var string[] Currencies codes in ISO-4217
     */
    private $currencyCodesWhichIsSupportedOnlyForInCountryPayments = [
        'BRL',
        'MYR'
    ];

    /**
     * @param string $currencyCode Currency code in ISO-4217
     *
     * @return bool
     */
    public function isSupportedCurrency($currencyCode)
    {
        $codes = $this->getSupportedCurrencyCodes();

        return in_array($currencyCode, $codes);
    }

    /**
     * @param string $currencyCode Currency code in ISO-4217
     *
     * @return bool
     */
    public function isFullySupportedCurrency($currencyCode)
    {
        $codes = $this->getFullySupportedCurrencyCodes();

        return in_array($currencyCode, $codes);
    }

    /**
     * @param string $currencyCode Currency code in ISO-4217
     *
     * @return bool
     */
    public function isCurrencyWithUnsupportedDecimals($currencyCode)
    {
        $codes = $this->getCurrencyCodesWhichIsNotSupportedDecimals();

        return in_array($currencyCode, $codes);
    }

    /**
     * @param string $currencyCode Currency code in ISO-4217
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
