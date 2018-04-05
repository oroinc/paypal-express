<?php

namespace Oro\Bundle\PayPalExpressBundle\Exception;

/**
 * Represents an interface of exception with an error context. Data in the context has no predefined structure.
 * This data is intended to be exposed as is when exception is logged.
 */
interface ErrorContextAwareExceptionInterface extends ExceptionInterface
{
    /**
     * Returns error context used for logging.
     *
     * @return array
     */
    public function getErrorContext();
}
