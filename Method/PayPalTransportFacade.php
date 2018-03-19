<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionDataFactory;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\MethodConfigTranslator;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalTransportInterface;

class PayPalTransportFacade implements PayPalTransportFacadeInterface
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
     * @var PaymentTransactionDataFactory
     */
    protected $paymentTransactionDataFactory;

    /**
     * @param PayPalTransportInterface      $payPalTransport
     * @param PaymentTransactionTranslator  $paymentTransactionTranslator
     * @param MethodConfigTranslator        $methodConfigTranslator
     * @param PaymentTransactionDataFactory $paymentTransactionDataFactory
     */
    public function __construct(
        PayPalTransportInterface $payPalTransport,
        PaymentTransactionTranslator $paymentTransactionTranslator,
        MethodConfigTranslator $methodConfigTranslator,
        PaymentTransactionDataFactory $paymentTransactionDataFactory
    ) {
        $this->payPalTransport               = $payPalTransport;
        $this->paymentTransactionTranslator  = $paymentTransactionTranslator;
        $this->methodConfigTranslator        = $methodConfigTranslator;
        $this->paymentTransactionDataFactory = $paymentTransactionDataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayPalPaymentRoute(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $paymentInfo = null;

        try {
            $this->updateRequest($paymentTransaction, $config);

            $paymentInfo        = $this->paymentTransactionTranslator->getPaymentInfo($paymentTransaction);
            $redirectRoutesInfo = $this->paymentTransactionTranslator->getRedirectRoutes($paymentTransaction);
            $apiContext         = $this->methodConfigTranslator->getApiContextInfo($config);

            $paymentRoute = $this->payPalTransport->setupPayment($paymentInfo, $apiContext, $redirectRoutesInfo);

            $paymentTransaction->setReference($paymentInfo->getPaymentId());
        } finally {
            $paymentTransactionData = $this->paymentTransactionDataFactory
                ->createResponseData($paymentTransaction, $config, $paymentInfo);

            $paymentTransaction->setResponse($paymentTransactionData->toArray());
        }

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
        $paymentInfo = null;

        try {
            $this->updateRequest($paymentTransaction, $config);

            $paymentInfo = $this->paymentTransactionTranslator->getPaymentInfo($paymentTransaction);
            $paymentInfo->setPaymentId($paymentId);
            $paymentInfo->setPayerId($payerId);

            $apiContext = $this->methodConfigTranslator->getApiContextInfo($config);

            $this->payPalTransport->executePayment($paymentInfo, $apiContext);
        } finally {
            $paymentTransactionData = $this->paymentTransactionDataFactory
                ->createResponseData($paymentTransaction, $config, $paymentInfo);

            $paymentTransaction->setResponse($paymentTransactionData->toArray());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function capturePayment(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $paymentInfo = null;

        try {
            $this->updateRequest($paymentTransaction, $config);

            $paymentInfo = $this->paymentTransactionTranslator->getPaymentInfo($paymentTransaction);

            $apiContext  = $this->methodConfigTranslator->getApiContextInfo($config);

            $this->payPalTransport->capturePayment($paymentInfo, $apiContext);
        } finally {
            $paymentTransactionData = $this->paymentTransactionDataFactory
                ->createResponseData($paymentTransaction, $config, $paymentInfo);

            $paymentTransaction->setResponse($paymentTransactionData->toArray());
        }
    }

    /**
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $config
     * @throws ExceptionInterface
     */
    public function authorizePayment(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        try {
            $this->updateRequest($paymentTransaction, $config);

            $paymentInfo = $this->paymentTransactionTranslator->getPaymentInfo($paymentTransaction);

            $apiContext  = $this->methodConfigTranslator->getApiContextInfo($config);

            $this->payPalTransport->authorizePayment($paymentInfo, $apiContext);
        } finally {
            $paymentTransactionData = $this->paymentTransactionDataFactory
                ->createResponseData($paymentTransaction, $config, $paymentInfo);

            $paymentTransaction->setResponse($paymentTransactionData->toArray());
        }
    }

    /**
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $config
     *
     * @throws ExceptionInterface
     */
    public function authorizeAndCapture(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        try {
            $this->updateRequest($paymentTransaction, $config);
            $paymentInfo = $this->paymentTransactionTranslator->getPaymentInfo($paymentTransaction);

            $apiContext = $this->methodConfigTranslator->getApiContextInfo($config);

            $this->payPalTransport->authorizePayment($paymentInfo, $apiContext);
        } finally {
            $paymentTransactionData = $this->paymentTransactionDataFactory
                ->createResponseData($paymentTransaction, $config, $paymentInfo);

            $paymentTransaction->setResponse($paymentTransactionData->toArray());
        }
    }

    /**
     * @param PaymentTransaction           $paymentTransaction
     * @param PayPalExpressConfigInterface $config
     */
    protected function updateRequest(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $paymentTransactionData = $this->paymentTransactionDataFactory
            ->createRequestData($paymentTransaction, $config);

        $paymentTransaction->setRequest($paymentTransactionData->toArray());
    }
}
