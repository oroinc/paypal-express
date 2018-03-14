<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\MethodConfigTranslator;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalTransportInterface;

class PayPalTransportFacade implements PayPalFacadeInterface
{
    /**
     * @var PayPalTransportInterface
     */
    protected $payPalTransport;

    /**
     * @var PaymentTransactionTranslator
     */
    protected $paymentTransactionTranslator;

    /**
     * @var MethodConfigTranslator
     */
    protected $methodConfigTranslator;

    /**
     * @param PayPalTransportInterface     $payPalTransport
     * @param PaymentTransactionTranslator $paymentTransactionTranslator
     * @param MethodConfigTranslator       $methodConfigTranslator
     */
    public function __construct(
        PayPalTransportInterface $payPalTransport,
        PaymentTransactionTranslator $paymentTransactionTranslator,
        MethodConfigTranslator $methodConfigTranslator
    ) {
        $this->payPalTransport              = $payPalTransport;
        $this->paymentTransactionTranslator = $paymentTransactionTranslator;
        $this->methodConfigTranslator       = $methodConfigTranslator;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayPalPaymentRoute(
        PaymentTransaction $paymentTransaction,
        PayPalExpressConfigInterface $config
    ) {
        $paymentInfo        = $this->paymentTransactionTranslator->getPaymentInfo($paymentTransaction);
        $redirectRoutesInfo = $this->paymentTransactionTranslator->getRedirectRoutes($paymentTransaction);
        $apiContext         = $this->methodConfigTranslator->getApiContextInfo($config);

        $paymentRoute = $this->payPalTransport->setupPayment($paymentInfo, $apiContext, $redirectRoutesInfo);

        return $paymentRoute;
    }

    /**
     * {@inheritdoc}
     */
    public function executePayPalPayment(
        PaymentTransaction $paymentTransaction,
        PayPalExpressConfigInterface $config,
        $paymentId,
        $payerId
    ) {
        $paymentInfo = $this->paymentTransactionTranslator->getPaymentInfo($paymentTransaction, $paymentId, $payerId);
        $apiContext  = $this->methodConfigTranslator->getApiContextInfo($config);

        $this->payPalTransport->executePayment($paymentInfo, $apiContext);
    }
}
