<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\DTO;

/**
 * Represents information necessary to use PayPal REST API.
 */
class ApiContextInfo
{
    /**
     * @var CredentialsInfo
     */
    protected $credentialsInfo;

    /**
     * @var bool
     */
    protected $isSandbox = true;

    /**
     * ApiContextInfo constructor.
     */
    public function __construct(CredentialsInfo $credentialsInfo, bool $isSandbox)
    {
        $this->credentialsInfo = $credentialsInfo;
        $this->isSandbox       = $isSandbox;
    }

    /**
     * @return CredentialsInfo
     */
    public function getCredentialsInfo()
    {
        return $this->credentialsInfo;
    }

    /**
     * @return bool
     */
    public function isSandbox()
    {
        return $this->isSandbox;
    }
}
