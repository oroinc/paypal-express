<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Behat\Mock\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\PayPalClient as BasePayPalClient;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalExpressTransport;
use PayPal\Api\Links;
use PayPal\Api\Payment;
use PayPal\Core\PayPalConstants;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

class PayPalClient extends BasePayPalClient
{
    /**
     * {@inheritdoc}
     */
    public function createPayment(Payment $payment, ApiContext $apiContext)
    {
        if (!$this->isPaymentValid($payment)) {
            throw new PayPalConnectionException(null, 'VALIDATION_ERROR');
        }

        $payment->setState(PayPalExpressTransport::PAYMENT_CREATED_STATUS);
        $payment->addLink(
            (new Links())->setRel(PayPalConstants::APPROVAL_URL)->setHref($payment->getRedirectUrls()->getReturnUrl())
        );

        return $payment;
    }

    /**
     * @param Payment $payment
     * @return bool
     */
    private function isPaymentValid(Payment $payment)
    {
        $transaction = $payment->getTransactions()[0];
        $transactionDetails = $transaction->getAmount()->getDetails();

        $expectedTotal = $transactionDetails->getSubtotal() +
            $transactionDetails->getShipping() +
            $transactionDetails->getTax();

        // comparing with delta to avoid direct "equals" comparison of float values
        if (abs($transaction->getAmount()->getTotal() - $expectedTotal) < 1e-6) {
            return true;
        }

        return false;
    }
}
