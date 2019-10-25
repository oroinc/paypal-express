<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;

class SupportedCurrenciesHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SupportedCurrenciesHelper
     */
    protected $helper;

    protected function setUp()
    {
        $this->helper = new SupportedCurrenciesHelper();
    }

    public function testIsSupportedCurrency()
    {
        $this->assertTrue($this->helper->isSupportedCurrency('USD'));
        $this->assertFalse($this->helper->isSupportedCurrency('UNKNOWN'));
    }

    public function testIsFullySupportedCurrency()
    {
        $this->assertTrue($this->helper->isFullySupportedCurrency('USD'));
        $this->assertFalse($this->helper->isFullySupportedCurrency('JPY'));
        $this->assertFalse($this->helper->isFullySupportedCurrency('BRL'));
    }

    public function testIsCurrencyWithUnsupportedDecimals()
    {
        $this->assertTrue($this->helper->isCurrencyWithUnsupportedDecimals('JPY'));
        $this->assertFalse($this->helper->isCurrencyWithUnsupportedDecimals('USD'));
    }

    public function testIsCurrencyCodeWhichIsSupportedOnlyForInCountryPayments()
    {
        $this->assertTrue($this->helper->isCurrencyCodeWhichIsSupportedOnlyForInCountryPayments('BRL'));
        $this->assertFalse($this->helper->isCurrencyCodeWhichIsSupportedOnlyForInCountryPayments('USD'));
    }

    public function testGetSupportedCurrencyCodes()
    {
        $expected = [
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
            'HUF',
            'JPY',
            'TWD',
            'BRL',
            'MYR'
        ];

        $actual = $this->helper->getSupportedCurrencyCodes();
        $this->assertEquals($expected, $actual);
    }

    public function testGetFullySupportedCurrencyCodes()
    {
        $expected = [
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

        $actual = $this->helper->getFullySupportedCurrencyCodes();
        $this->assertEquals($expected, $actual);
    }

    public function testGetCurrencyCodesWhichIsNotSupportedDecimals()
    {
        $actual = $this->helper->getCurrencyCodesWhichIsNotSupportedDecimals();
        $this->assertEquals(['HUF', 'JPY', 'TWD',], $actual);
    }

    public function testGetCurrencyCodesWhichIsSupportedOnlyForInCountryPayments()
    {
        $actual = $this->helper->getCurrencyCodesWhichIsSupportedOnlyForInCountryPayments();
        $this->assertEquals(['BRL', 'MYR'], $actual);
    }
}
