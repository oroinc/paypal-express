<?php

namespace Oro\Bundle\PayPalExpressBundle\Method;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionDataFactory;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionResponseData;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\MethodConfigTranslator;
use Oro\Bundle\PayPalExpressBundle\Method\Translator\PaymentTransactionTranslator;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalExpressTransportInterface;

/**
 * Provides clear API for client and encapsulate next logic:
 * - Using {@see PayPalExpressTransport}
 * - Setting {@see PaymentTransaction::$request}
 * - Setting {@see PaymentTransaction::$response}
 * - Using {@see PaymentTransactionTranslator} and {@see MethodConfigTranslator}
 */
class PayPalExpressTransportFacade implements PayPalExpressTransportFacadeInterface
{
    /**
     * @var PayPalExpressTransportInterface
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

    public function __construct(
        PayPalExpressTransportInterface $payPalTransport,
        PaymentTransactionTranslator $paymentTransactionTranslator,
        MethodConfigTranslator $methodConfigTranslator,
        PaymentTransactionDataFactory $paymentTransactionDataFactory
    ) {
        $this->payPalTransport = $payPalTransport;
        $this->paymentTransactionTranslator = $paymentTransactionTranslator;
        $this->methodConfigTranslator = $methodConfigTranslator;
        $this->paymentTransactionDataFactory = $paymentTransactionDataFactory;
    }

    #[\Override]
    public function getPayPalPaymentRoute(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $paymentInfo = null;

        try {
            $this->updateRequest($paymentTransaction, $config);

            $paymentInfo = $this->createPaymentInfo($paymentTransaction);
            $apiContext = $this->getApiContextInfo($config);
            $redirectRoutesInfo = $this->createRedirectRoutesInfo($paymentTransaction);

            $paymentRoute = $this->payPalTransport->setupPayment($paymentInfo, $apiContext, $redirectRoutesInfo);

            $paymentTransaction->setReference($paymentInfo->getPaymentId());
        } finally {
            $this->updateResponse($paymentTransaction, $config, $paymentInfo);
        }

        return $paymentRoute;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return PaymentInfo
     */
    protected function createPaymentInfo(PaymentTransaction $paymentTransaction)
    {
        return $this->paymentTransactionTranslator->getPaymentInfo($paymentTransaction);
    }

    /**
     * @param PayPalExpressConfigInterface $config
     * @return ApiContextInfo
     */
    protected function getApiContextInfo(PayPalExpressConfigInterface $config)
    {
        return $this->methodConfigTranslator->getApiContextInfo($config);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return RedirectRoutesInfo
     */
    protected function createRedirectRoutesInfo(PaymentTransaction $paymentTransaction)
    {
        return $this->paymentTransactionTranslator->getRedirectRoutes($paymentTransaction);
    }

    #[\Override]
    public function executePayPalPayment(
        PaymentTransaction $paymentTransaction,
        PayPalExpressConfigInterface $config
    ) {
        $this->updateRequest($paymentTransaction, $config);
        $paymentInfo = $this->createPaymentInfoForExecutePayment($paymentTransaction);
        $apiContext = $this->getApiContextInfo($config);

        try {
            $this->payPalTransport->executePayment($paymentInfo, $apiContext);
        } finally {
            $this->updateResponse($paymentTransaction, $config, $paymentInfo);
        }
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return PaymentInfo
     */
    protected function createPaymentInfoForExecutePayment(PaymentTransaction $paymentTransaction)
    {
        $paymentInfo = $this->createPaymentInfo($paymentTransaction);

        $responseData = $this->createResponseDataByPaymentTransaction($paymentTransaction);
        $paymentInfo->setPaymentId($responseData->getPaymentId());
        $paymentInfo->setPayerId($responseData->getPayerId());

        return $paymentInfo;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return PaymentTransactionResponseData
     */
    protected function createResponseDataByPaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        $responseData = $this->paymentTransactionDataFactory
            ->createResponseDataFromArray($paymentTransaction->getResponse());

        return $responseData;
    }

    #[\Override]
    public function capturePayment(
        PaymentTransaction $paymentTransaction,
        PaymentTransaction $authorizedTransaction,
        PayPalExpressConfigInterface $config
    ) {
        $this->updateRequest($paymentTransaction, $config);
        $paymentInfo = $this->createPaymentInfoForCapturePayment($paymentTransaction, $authorizedTransaction);
        $apiContext = $this->getApiContextInfo($config);

        try {
            $this->payPalTransport->capturePayment($paymentInfo, $apiContext);
        } finally {
            $this->updateResponse($paymentTransaction, $config, $paymentInfo);
        }
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param PaymentTransaction $authorizedTransaction
     * @return PaymentInfo
     */
    protected function createPaymentInfoForCapturePayment(
        PaymentTransaction $paymentTransaction,
        PaymentTransaction $authorizedTransaction
    ) {
        $paymentInfo = $this->createPaymentInfo($paymentTransaction);

        $responseData = $this->createResponseDataByPaymentTransaction($authorizedTransaction);
        $paymentInfo->setOrderId($responseData->getOrderId());
        $paymentInfo->setPaymentId($responseData->getPaymentId());
        $paymentInfo->setPayerId($responseData->getPayerId());

        return $paymentInfo;
    }

    #[\Override]
    public function authorizePayment(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $this->updateRequest($paymentTransaction, $config);
        $paymentInfo = $this->createPaymentInfoForAuthorizePayment($paymentTransaction);
        $apiContext = $this->getApiContextInfo($config);

        try {
            $this->payPalTransport->authorizePayment($paymentInfo, $apiContext);
        } finally {
            $this->updateResponse($paymentTransaction, $config, $paymentInfo);
        }
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return PaymentInfo
     */
    protected function createPaymentInfoForAuthorizePayment(PaymentTransaction $paymentTransaction)
    {
        $paymentInfo = $this->createPaymentInfo($paymentTransaction);

        $responseData = $this->createResponseDataByPaymentTransaction($paymentTransaction);
        $paymentInfo->setOrderId($responseData->getOrderId());
        $paymentInfo->setPaymentId($responseData->getPaymentId());
        $paymentInfo->setPayerId($responseData->getPayerId());

        return $paymentInfo;
    }

    protected function updateRequest(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        $requestData = $this->paymentTransactionDataFactory
            ->createRequestData($paymentTransaction, $config);

        $paymentTransaction->setRequest($requestData->toArray());
    }

    protected function updateResponse(
        PaymentTransaction $paymentTransaction,
        PayPalExpressConfigInterface $config,
        PaymentInfo $paymentInfo = null
    ) {
        $responseData = $this->paymentTransactionDataFactory
            ->createResponseData($paymentTransaction, $config, $paymentInfo);

        $paymentTransaction->setResponse($responseData->toArray());
    }
}
