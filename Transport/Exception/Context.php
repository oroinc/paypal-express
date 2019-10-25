<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\Exception;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ErrorInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\Payment;

/**
 * Represents {@see TransportException} context.
 */
class Context
{
    /**
     * @var PaymentInfo|null
     */
    protected $paymentInfo = null;

    /**
     * @var Payment|null
     */
    protected $payment = null;

    /**
     * @var Authorization|null
     */
    protected $authorization = null;

    /**
     * @var Capture|null
     */
    protected $capture = null;

    /**
     * @var ErrorInfo|null
     */
    protected $errorInfo = null;

    /**
     * @param PaymentInfo|null $paymentInfo
     *
     * @return $this
     */
    public function setPaymentInfo(PaymentInfo $paymentInfo = null)
    {
        $this->paymentInfo = $paymentInfo;

        return $this;
    }

    /**
     * @param Payment|null $payment
     *
     * @return $this
     */
    public function setPayment(Payment $payment = null)
    {
        $this->payment = $payment;

        return $this;
    }

    /**
     * @param Authorization|null $authorization
     *
     * @return $this
     */
    public function setAuthorization(Authorization $authorization = null)
    {
        $this->authorization = $authorization;

        return $this;
    }

    /**
     * @param Capture|null $capture
     *
     * @return $this
     */
    public function setCapture(Capture $capture = null)
    {
        $this->capture = $capture;

        return $this;
    }

    /**
     * @param ErrorInfo|null $errorInfo
     *
     * @return $this
     */
    public function addErrorInfo(ErrorInfo $errorInfo = null)
    {
        $this->errorInfo = $errorInfo;

        return $this;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        $context = [];

        if ($this->paymentInfo) {
            $context['payment_info']['payment_id'] = $this->paymentInfo->getPaymentId();
            $context['payment_info']['order_id']   = $this->paymentInfo->getOrderId();
        }

        if ($this->payment) {
            $context['payment']['id']             = $this->payment->getId();
            $context['payment']['state']          = $this->payment->getState();
            $context['payment']['failure_reason'] = $this->payment->getFailureReason();
        }

        if ($this->authorization) {
            $context['authorization']['state']       = $this->authorization->getState();
            $context['authorization']['reason_code'] = $this->authorization->getReasonCode();
            $context['authorization']['valid_until'] = $this->authorization->getValidUntil();
        }

        if ($this->capture) {
            $context['capture']['parent_payment'] = $this->capture->getParentPayment();
            $context['capture']['state']          = $this->capture->getState();
        }

        if ($this->errorInfo) {
            $context['error_info'] = $this->errorInfo->toArray();
        }

        return $context;
    }
}
