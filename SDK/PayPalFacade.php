<?php

namespace Oro\Bundle\PayPalExpressBundle\SDK;

use Oro\Bundle\PayPalExpressBundle\SDK\DTO\CredentialsInfo;
use Oro\Bundle\PayPalExpressBundle\SDK\DTO\PaymentInfo;

use PayPal\Exception\PayPalConnectionException;

use Psr\Log\LoggerInterface;

class PayPalFacade
{
    /**
     * @var PayPalObjectsTranslator
     */
    protected $payPalObjectTranslator;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param PaymentInfo     $paymentInfo
     * @param CredentialsInfo $credentialsInfo
     * @param string          $successRoute Route where PayPal will redirect user after payment approve
     * @param string          $failedRoute Route where PayPal will redirect user after payment cancel
     *
     * @return string Link where user should approve payment
     * @throws PayPalConnectionException
     * @throws \Throwable
     */
    public function setupPayment(
        PaymentInfo $paymentInfo,
        CredentialsInfo $credentialsInfo,
        $successRoute,
        $failedRoute
    ) {
        try {
            $payment = $this->payPalObjectTranslator->getPayment($paymentInfo, $successRoute, $failedRoute);
            $apiContext = $this->payPalObjectTranslator->getApiContext($credentialsInfo);
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
