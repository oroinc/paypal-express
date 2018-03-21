<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\DTO;

class ExceptionInfo
{
    /**
     * @var string Error message
     */
    protected $message;

    /**
     * @var string Error status code
     *
     */
    protected $statusCode;

    /**
     * @var string Error details usually user friendly description
     *
     */
    protected $details;

    /**
     * @var string Link to related resource if exist
     */
    protected $relatedResourceLink;

    /**
     * @var string Pay Pal debug ID
     */
    protected $debugId;

    /**
     * @var PaymentInfo
     */
    protected $relatedPayment;

    /**
     * @var mixed Response data could contain sensitive information and should not be stored unencrypted
     */
    protected $rawData;

    /**
     * @param string      $message
     * @param string      $statusCode
     * @param string      $details
     * @param string      $relatedResourceLink
     * @param string      $debugId
     * @param PaymentInfo $relatedPayment
     * @param mixed       $rawData
     */
    public function __construct(
        $message,
        $statusCode,
        $details,
        $relatedResourceLink,
        $debugId,
        PaymentInfo $relatedPayment,
        $rawData = null
    ) {
        $this->message             = $message;
        $this->statusCode          = $statusCode;
        $this->details             = $details;
        $this->relatedResourceLink = $relatedResourceLink;
        $this->relatedPayment      = $relatedPayment;
        $this->debugId             = $debugId;
        $this->rawData             = $rawData;
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
    public function getStatusCode()
    {
        return $this->statusCode;
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
    public function getRelatedResourceLink()
    {
        return $this->relatedResourceLink;
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
}
