<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\Exception;

interface TransportExceptionFactoryInterface
{
    /**
     * @param string          $message
     * @param array           $context
     * @param \Throwable|null $throwable
     * @return TransportException
     */
    public function createTransportException($message, array $context = [], \Throwable $throwable = null);
}
