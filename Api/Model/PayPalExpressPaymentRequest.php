<?php

namespace Oro\Bundle\PayPalExpressBundle\Api\Model;

/**
 * Represents the PayPal Express payment request.
 */
final class PayPalExpressPaymentRequest
{
    private ?string $successUrl = null;
    private ?string $failureUrl = null;

    public function getSuccessUrl(): ?string
    {
        return $this->successUrl;
    }

    public function setSuccessUrl(?string $successUrl): void
    {
        $this->successUrl = $successUrl;
    }

    public function getFailureUrl(): ?string
    {
        return $this->failureUrl;
    }

    public function setFailureUrl(?string $failureUrl): void
    {
        $this->failureUrl = $failureUrl;
    }
}
