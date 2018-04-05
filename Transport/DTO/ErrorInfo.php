<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\DTO;

/**
 * Represents PayPal REST API error.
 *
 * @link https://developer.paypal.com/docs/api/payments/#definition-error
 */
class ErrorInfo
{
    /**
     * @var string Error message
     */
    protected $message;

    /**
     * @var string Error status code
     *
     */
    protected $name;

    /**
     * @var string Error details usually user friendly description
     *
     */
    protected $details;

    /**
     * @var string Link to related resource if exist
     */
    protected $informationLink;

    /**
     * @var string Pay Pal debug ID
     */
    protected $debugId;

    /**
     * @var mixed Response data could contain sensitive information and should not be stored unencrypted
     */
    protected $rawData;

    /**
     * @param string $message
     * @param string $name
     * @param string $details
     * @param string $informationLink
     * @param string $debugId
     * @param mixed  $rawData
     */
    public function __construct(
        $message,
        $name,
        $details,
        $informationLink,
        $debugId,
        $rawData = null
    ) {
        $this->message = $message;
        $this->name = $name;
        $this->details = $details;
        $this->informationLink = $informationLink;
        $this->debugId = $debugId;
        $this->rawData = $rawData;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @return string
     */
    public function getInformationLink()
    {
        return $this->informationLink;
    }

    /**
     * @return string
     */
    public function getDebugId()
    {
        return $this->debugId;
    }

    /**
     * @return mixed
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    public function toArray()
    {
        return [
            'message'          => $this->getMessage(),
            'name'             => $this->getName(),
            'details'          => $this->getDetails(),
            'information_link' => $this->getInformationLink(),
            'debug_id'         => $this->getDebugId(),
        ];
    }
}
