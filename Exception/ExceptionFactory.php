<?php

namespace Oro\Bundle\PayPalExpressBundle\Exception;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ExceptionInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;

class ExceptionFactory
{
    /**
     * @var SupportedCurrenciesHelper
     */
    protected $supportedCurrenciesHelper;

    /**
     * @param SupportedCurrenciesHelper $supportedCurrenciesHelper
     */
    public function __construct(SupportedCurrenciesHelper $supportedCurrenciesHelper)
    {
        $this->supportedCurrenciesHelper = $supportedCurrenciesHelper;
    }

    /**
     * @param string        $message
     * @param ExceptionInfo $exceptionInfo
     * @param \Exception    $previous
     *
     * @return ConnectionException
     */
    public function createConnectionException($message, ExceptionInfo $exceptionInfo, \Exception $previous)
    {
        $message = sprintf(
            '%s. Reason: %s, Code: %s, Information Link: %s',
            $message,
            $exceptionInfo->getMessage(),
            $exceptionInfo->getStatusCode(),
            $exceptionInfo->getRelatedResourceLink()
        );

        $connectionException = new ConnectionException($message, $previous->getCode(), $previous);
        $connectionException->setExceptionInfo($exceptionInfo);

        return $connectionException;
    }

    /**
     * @param string      $message
     * @param string|null $failureReason
     *
     * @return OperationExecutionFailedException
     */
    public function createOperationExecutionFailedException($message, $failureReason = null)
    {
        if ($failureReason) {
            $message .= ". Reason: $failureReason";
        }

        return new OperationExecutionFailedException($message);
    }

    /**
     * @param string          $invalidCurrency
     * @param \Throwable|null $previous
     *
     * @return UnsupportedCurrencyException
     */
    public function createUnsupportedCurrencyException(
        $invalidCurrency,
        \Throwable $previous = null
    ) {
        $message = sprintf('Currency "%s" is not supported.', $invalidCurrency);

        $listOfSupportedCurrencies = $this->supportedCurrenciesHelper->getSupportedCurrencyCodes();
        if ($listOfSupportedCurrencies) {
            $message .= sprintf(' Only next currencies are supported: "%s"', implode($listOfSupportedCurrencies));
        }

        return new UnsupportedCurrencyException(
            $message,
            0,
            $previous
        );
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
