<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\Environment\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\PayPalClient;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalExpressTransport;
use PayPal\Api\Links;
use PayPal\Api\Payment;
use PayPal\Converter\FormatConverter;
use PayPal\Core\PayPalConstants;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

class PayPalClientMock extends PayPalClient
{
    /**
     * A payment id functional tests use to force the post-redirect "complete" step
     * (@see \Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CompleteVirtualAction) to fail, the same way a
     * real PayPal API error would - none of the existing fixtures/tests exercise this call successfully, so
     * there is no real behavior here to preserve for any other payment id.
     */
    public const FAILING_PAYMENT_ID = 'FAILING-PAYMENT-ID';

    #[\Override]
    public function createPayment(Payment $payment, ApiContext $apiContext)
    {
        if (!$this->isPaymentValid($payment)) {
            throw new PayPalConnectionException(null, 'VALIDATION_ERROR');
        }

        $payment->setState(PayPalExpressTransport::PAYMENT_CREATED_STATUS);
        $payment->addLink(
            (new Links())->setRel(PayPalConstants::APPROVAL_URL)->setHref('http://paypal.com/redirect')
        );

        return $payment;
    }

    #[\Override]
    public function getPaymentById($paymentId, ApiContext $apiContext)
    {
        if ($paymentId === self::FAILING_PAYMENT_ID) {
            throw new PayPalConnectionException(null, 'PAYMENT_NOT_FOUND');
        }

        return parent::getPaymentById($paymentId, $apiContext);
    }

    private function isPaymentValid(Payment $payment): bool
    {
        $transaction = $payment->getTransactions()[0];
        $transactionDetails = $transaction->getAmount()->getDetails();

        $expectedTotal =
            FormatConverter::formatToNumber($transactionDetails->getSubtotal())
            + FormatConverter::formatToNumber($transactionDetails->getShipping())
            + FormatConverter::formatToNumber($transactionDetails->getTax());

        // comparing with a delta to avoid direct "equals" comparison of float values
        return abs(FormatConverter::formatToNumber($transaction->getAmount()->getTotal()) - $expectedTotal) < 1e-6;
    }
}
