<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;

class PayPalFacade implements PayPalFacadeInterface
{
    /**
     * @var PayPalTransportInterface
     */
    protected $payPalTransport;

    /**
     * @var PaymentInfoTranslator
     */
    protected $paymentInfoTranslator;

    /**
     * @param PayPalTransportInterface $payPalTransport
     * @param PaymentInfoTranslator    $paymentInfoTranslator
     */
    public function __construct(PayPalTransportInterface $payPalTransport, PaymentInfoTranslator $paymentInfoTranslator)
    {
        $this->payPalTransport       = $payPalTransport;
        $this->paymentInfoTranslator = $paymentInfoTranslator;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayPalPaymentRoute(
        PaymentTransaction $paymentTransaction,
        $clentId,
        $clientSecret,
        $successRoute,
        $failedRoute
    ) {
        $paymentInfo = $this->paymentInfoTranslator->getPaymentInfo($paymentTransaction);
        $paymentRoute = $this->payPalTransport
            ->setupPayment($paymentInfo, new CredentialsInfo($clentId, $clientSecret), $successRoute, $failedRoute);

        return $paymentRoute;
    }

    /**
     * {@inheritdoc}
     */
    public function executePayPalPayment(
        PaymentTransaction $paymentTransaction,
        $clentId,
        $clientSecret,
        $paymentId,
        $payerId
    ) {
        $paymentInfo = $this->paymentInfoTranslator->getPaymentInfo($paymentTransaction, $paymentId, $payerId);

        $this->payPalTransport->executePayment($paymentInfo, new CredentialsInfo($clentId, $clientSecret));
    }
}
