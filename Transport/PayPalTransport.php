<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;

use PayPal\Api\Order;
use PayPal\Exception\PayPalConnectionException;

use Psr\Log\LoggerInterface;

class PayPalTransport implements PayPalTransportInterface
{
    /**
     * @var PayPalSDKObjectTranslator
     */
    protected $payPalSDKObjectTranslator;

    /**
     * @var PayPalClient
     */
    protected $payPalClient;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param PayPalSDKObjectTranslator $payPalSDKObjectTranslator
     * @param PayPalClient              $payPalClient
     * @param LoggerInterface           $logger
     */
    public function __construct(
        PayPalSDKObjectTranslator $payPalSDKObjectTranslator,
        PayPalClient $payPalClient,
        LoggerInterface $logger
    ) {
        $this->payPalSDKObjectTranslator = $payPalSDKObjectTranslator;
        $this->payPalClient              = $payPalClient;
        $this->logger                    = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setupPayment(
        PaymentInfo $paymentInfo,
        CredentialsInfo $credentialsInfo,
        $successRoute,
        $failedRoute
    ) {
        try {
            $payment = $this->payPalSDKObjectTranslator->getPayment($paymentInfo, $successRoute, $failedRoute);
            $apiContext = $this->payPalSDKObjectTranslator->getApiContext($credentialsInfo);
            $payment = $this->payPalClient->createPayment($payment, $apiContext);

            return $payment->getApprovalLink();
        } catch (PayPalConnectionException $connectionException) {
            $this->logger->error(
                sprintf(
                    'Could not connect to PayPal server. Reason: %s',
                    $connectionException->getMessage()
                ),
                [
                    'exception' => $connectionException
                ]
            );

            throw $connectionException;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not create payment for PayPal. Reason: %s',
                    $exception->getMessage()
                ),
                [
                    'exception' => $exception
                ]
            );

            throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executePayment(PaymentInfo $paymentInfo, CredentialsInfo $credentialsInfo)
    {
        try {
            $apiContext = $this->payPalSDKObjectTranslator->getApiContext($credentialsInfo);
            $payment = $this->payPalClient->getPaymentById($paymentInfo->getPaymentId(), $apiContext);

            $execution = $this->payPalSDKObjectTranslator->getPaymentExecution($paymentInfo);
            $this->payPalClient->executePayment($payment, $execution, $apiContext);

            /** @var Order $order */
            $order = $payment->transactions[0]->related_resources[0]->order;

            $authorization = $this->payPalSDKObjectTranslator->getAuthorization($paymentInfo);
            $this->payPalClient->authorizeOrder($order, $authorization, $apiContext);

            $captureDetails = $this->payPalSDKObjectTranslator->getCapturedDetails($paymentInfo);
            $this->payPalClient->captureOrder($order, $captureDetails, $apiContext);
        } catch (PayPalConnectionException $connectionException) {
            $this->logger->error(
                sprintf(
                    'Could not connect to PayPal server. Reason: %s',
                    $connectionException->getMessage()
                ),
                [
                    'exception' => $connectionException
                ]
            );

            throw $connectionException;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not execute payment. Reason: %s',
                    $exception->getMessage()
                ),
                [
                    'exception' => $exception
                ]
            );

            throw $exception;
        }
    }
}
