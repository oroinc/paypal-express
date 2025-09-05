<?php

namespace Oro\Bundle\PayPalExpressBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorMetaProperty;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\CheckoutBundle\Api\Processor\AbstractHandlePaymentSubresource;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PayPalExpressBundle\Api\Model\PayPalExpressPaymentRequest;

/**
 * Handles the checkout PayPal Express payment sub-resource.
 */
class HandlePayPalExpressPaymentSubresource extends AbstractHandlePaymentSubresource
{
    #[\Override]
    protected function getInProgressStatuses(): array
    {
        return [
            PaymentStatuses::PENDING
        ];
    }

    #[\Override]
    protected function getErrorStatuses(): array
    {
        return [
            PaymentStatuses::CANCELED,
            PaymentStatuses::DECLINED
        ];
    }

    #[\Override]
    protected function getPaymentTransactionOptions(
        Checkout $checkout,
        ChangeSubresourceContext $context
    ): array {
        // URLs must be provided because PayPal express processes payment at its own website, and
        // it should return buyer somewhere
        /** @var PayPalExpressPaymentRequest $request */
        $request = $context->getResult()[$context->getAssociationName()];

        return [
            'failureUrl' => $request->getFailureUrl(),
            'successUrl' => $request->getSuccessUrl()
        ];
    }

    #[\Override]
    protected function processPaymentError(
        Checkout $checkout,
        Order $order,
        array $paymentResult,
        ChangeSubresourceContext $context
    ): void {
        if (!empty($paymentResult['purchaseRedirectUrl'])) {
            $context->addError($this->createRequireAdditionalActionError($paymentResult));

            return;
        }

        $this->onPaymentError($checkout, $context);
        $this->saveChanges($context);
        $context->addError(Error::createValidationError(
            'payment constraint',
            $this->getPaymentErrorDetail($paymentResult)
        ));
    }

    private function createRequireAdditionalActionError(array $paymentResult): Error
    {
        $error = Error::createValidationError(
            'payment action constraint',
            'Payment should be completed on the merchant\'s page, follow the link provided in the error details.'
        );
        $error->addMetaProperty('data', new ErrorMetaProperty($paymentResult, 'array'));

        return $error;
    }

    private function getPaymentErrorDetail(array $paymentResult): string
    {
        return $paymentResult['error'] ?? 'Payment failed, please try again or select a different payment method.';
    }
}
