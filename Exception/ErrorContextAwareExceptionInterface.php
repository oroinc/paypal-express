<?php

namespace Oro\Bundle\PayPalExpressBundle\Exception;

interface ErrorContextAwareExceptionInterface extends ExceptionInterface
{
    /**
     * Returns error context used for logging.
     *
     * @return array
     */
    public function getErrorContext();
}
