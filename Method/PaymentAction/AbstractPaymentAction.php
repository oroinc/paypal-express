<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\ErrorContextAwareExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalExpressTransportFacadeInterface;
use Psr\Log\LoggerInterface;

/**
 * Contains default implementation of error handling.
 */
abstract class AbstractPaymentAction implements PaymentActionInterface
{
    /**
     * @var PayPalExpressTransportFacadeInterface
     */
    protected $payPalTransportFacade;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        PayPalExpressTransportFacadeInterface $payPalTransportFacade,
        LoggerInterface $logger
    ) {
        $this->payPalTransportFacade = $payPalTransportFacade;
        $this->logger = $logger;
    }

    /**
     * Updates transaction with failed state and handles error.
     *
     * @throws \Throwable
     */
    protected function handlePaymentTransactionError(
        PaymentTransaction $paymentTransaction,
        \Throwable $exceptionOrError
    ) {
        $this->setPaymentTransactionStateFailed($paymentTransaction);
        $this->handleError($paymentTransaction, $exceptionOrError);
    }

    protected function setPaymentTransactionStateFailed(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction
            ->setSuccessful(false)
            ->setActive(false);
    }

    /**
     * Logs error. Re-throw exception/error again in case if it is not an expected payment exception.
     *
     * @throws \Throwable
     */
    protected function handleError(PaymentTransaction $paymentTransaction, \Throwable $exceptionOrError)
    {
        $errorMessage = $this->getErrorLogMessage($exceptionOrError);
        $errorContext = $this->getErrorLogContext($paymentTransaction, $exceptionOrError);
        $this->logError($errorMessage, $errorContext);

        if ($this->isPaymentException($exceptionOrError)) {
            throw $exceptionOrError;
        }
    }

    /**
     * @param \Throwable $exceptionOrError
     * @return string
     */
    protected function getErrorLogMessage(\Throwable $exceptionOrError)
    {
        return sprintf(
            'Payment %s failed. Reason: %s',
            $this->getName(),
            $exceptionOrError->getMessage()
        );
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param \Throwable         $exceptionOrError
     * @return array
     */
    protected function getErrorLogContext(PaymentTransaction $paymentTransaction, \Throwable $exceptionOrError)
    {
        $result = [
            'payment_transaction_id' => $paymentTransaction->getId(),
            'payment_method'         => $paymentTransaction->getPaymentMethod(),
            'exception'              => $exceptionOrError
        ];

        if ($exceptionOrError instanceof ErrorContextAwareExceptionInterface) {
            $result = array_merge($result, $exceptionOrError->getErrorContext());
        }

        return $result;
    }

    /**
     * @param string $message
     * @param array  $errorContext
     */
    protected function logError($message, array $errorContext)
    {
        $this->logger->error($message, $errorContext);
    }

    /**
     * @param \Throwable $exceptionOrError
     * @return bool
     */
    protected function isPaymentException(\Throwable $exceptionOrError)
    {
        return !$exceptionOrError instanceof ExceptionInterface;
    }
}
