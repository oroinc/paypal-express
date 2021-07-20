<?php

namespace Oro\Bundle\PayPalExpressBundle\Exception;

use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;

/**
 * Factory for PayPalExpressBundle Exceptions, should help client to create those exception
 * and also will help to customize exceptions in customizations
 */
class ExceptionFactory
{
    /**
     * @var SupportedCurrenciesHelper
     */
    protected $supportedCurrenciesHelper;

    public function __construct(SupportedCurrenciesHelper $supportedCurrenciesHelper)
    {
        $this->supportedCurrenciesHelper = $supportedCurrenciesHelper;
    }

    /**
     * @param string $invalidCurrency
     *
     * @return UnsupportedCurrencyException
     */
    public function createUnsupportedCurrencyException($invalidCurrency)
    {
        $listOfSupportedCurrencies = $this->supportedCurrenciesHelper->getSupportedCurrencyCodes();
        $message = sprintf(
            'Currency "%s" is not supported. Only next currencies are supported: "%s"',
            $invalidCurrency,
            implode(', ', $listOfSupportedCurrencies)
        );

        return new UnsupportedCurrencyException($message);
    }

    /**
     * @param string $message
     *
     * @return UnsupportedValueException
     */
    public function createUnsupportedValueException($message)
    {
        return new UnsupportedValueException($message);
    }

    /**
     * @param string          $message
     * @param \Throwable|null $previous
     *
     * @return LogicException
     */
    public function createLogicException($message, \Throwable $previous = null)
    {
        return new LogicException($message, 0, $previous);
    }

    /**
     * @param string          $message
     * @param \Throwable|null $previous
     *
     * @return RuntimeException
     */
    public function createRuntimeException($message, \Throwable $previous = null)
    {
        return new RuntimeException($message, 0, $previous);
    }
}
