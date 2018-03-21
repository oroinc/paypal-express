<?php

namespace Oro\Bundle\PayPalExpressBundle\Exception;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ExceptionInfo;

class ConnectionException extends RuntimeException
{
    /**
     * @var ExceptionInfo
     */
    protected $exceptionInfo;

    /**
     * @return ExceptionInfo
     */
    public function getExceptionInfo()
    {
        return $this->exceptionInfo;
    }

    /**
     * @param ExceptionInfo $exceptionInfo
     */
    public function setExceptionInfo(ExceptionInfo $exceptionInfo)
    {
        $this->exceptionInfo = $exceptionInfo;
    }
}
