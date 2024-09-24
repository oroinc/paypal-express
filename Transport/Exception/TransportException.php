<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\Exception;

use Oro\Bundle\PayPalExpressBundle\Exception\ErrorContextAwareExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;

/**
 * Represents specific Transport Exception which contain error context.
 * Data in the context has no predefined structure.
 * This data is intended to be exposed as is when exception is logged.
 */
class TransportException extends RuntimeException implements ErrorContextAwareExceptionInterface
{
    /**
     * @var array
     */
    protected $errorContext = [];

    public function __construct(string $message = "", array $errorContext = [], \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->errorContext = $errorContext;
    }

    /**
     * @return array
     */
    #[\Override]
    public function getErrorContext()
    {
        return $this->errorContext;
    }
}
