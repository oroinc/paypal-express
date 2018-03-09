<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;

use PayPal\Exception\PayPalConnectionException;

use Psr\Log\LoggerInterface;

class PayPalTransport implements PayPalTransportInterface
{
    /**
     * @var PayPalSDKObjectTranslator
     */
    protected $payPalSDKObjectTranslator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param PayPalSDKObjectTranslator $payPalSDKObjectTranslator
     * @param LoggerInterface           $logger
     */
    public function __construct(PayPalSDKObjectTranslator $payPalSDKObjectTranslator, LoggerInterface $logger)
    {
        $this->payPalSDKObjectTranslator = $payPalSDKObjectTranslator;
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
            $payment->create($apiContext);

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
}
