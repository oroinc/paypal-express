<?php

namespace Oro\Bundle\PayPalExpressBundle\Exception;

/**
 * Represent public interface of exception, which contains an error context
 * Will help to receive error context in
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
