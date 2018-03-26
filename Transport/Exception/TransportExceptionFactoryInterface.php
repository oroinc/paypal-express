<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\Exception;

interface TransportExceptionFactoryInterface
{
    /**
     * @param string          $message
     * @param Context         $context
     * @param \Throwable|null $throwable
     *
     * @return TransportException
     */
    public function createTransportException($message, Context $context, \Throwable $throwable = null);
}
