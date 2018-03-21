<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Exception\ConnectionException;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;

use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\Order;
use PayPal\Api\Payment;
use PayPal\Api\RelatedResources;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;

use Psr\Log\LoggerInterface;

class PayPalTransport implements PayPalTransportInterface
{
    const PAYMENT_CREATED_STATUS = 'created';
    const PAYMENT_EXECUTED_STATUS = 'approved';
    const ORDER_PAYMENT_AUTHORIZED_STATUS = 'authorized';
    const ORDER_PAYMENT_CAPTURED_STATUS = 'completed';

    /**
     * @var PayPalSDKObjectTranslatorInterface
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
     * @var ExceptionFactory
     */
    protected $exceptionFactory;

    /**
     * @param PayPalSDKObjectTranslatorInterface $payPalSDKObjectTranslator
     * @param PayPalClient                       $payPalClient
     * @param LoggerInterface                    $logger
     * @param ExceptionFactory                   $exceptionFactory
     */
    public function __construct(
        PayPalSDKObjectTranslatorInterface $payPalSDKObjectTranslator,
        PayPalClient $payPalClient,
        LoggerInterface $logger,
        ExceptionFactory $exceptionFactory
    ) {
        $this->payPalSDKObjectTranslator = $payPalSDKObjectTranslator;
        $this->payPalClient              = $payPalClient;
        $this->logger                    = $logger;
        $this->exceptionFactory = $exceptionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function setupPayment(
        PaymentInfo $paymentInfo,
        ApiContextInfo $apiContextInfo,
        RedirectRoutesInfo $redirectRoutesInfo
    ) {
        try {
            $payment = $this->payPalSDKObjectTranslator->getPayment($paymentInfo, $redirectRoutesInfo);
            $apiContext = $this->payPalSDKObjectTranslator->getApiContext($apiContextInfo);
            $payment = $this->payPalClient->createPayment($payment, $apiContext);

            $paymentInfo->setPaymentId($payment->getId());
        } catch (PayPalConnectionException $connectionException) {
            $this->processConnectionException($connectionException, $paymentInfo, 'Could not create payment');
        } catch (\Throwable $exception) {
            $this->processException($exception, 'Could not create payment');
        }

        if ($payment->getState() != self::PAYMENT_CREATED_STATUS) {
            $message = 'Could not create the payment.';

            $this->logger->error(
                $message,
                [
                    'Payment state'  => $payment->getState(),
                    'Payment id'     => $payment->getId(),
                    'Failure reason' => $payment->getFailureReason(),
                ]
            );

            $exception = $this->exceptionFactory
                ->createOperationExecutionFailedException($message, $payment->getFailureReason());
            throw $exception;
        }



        return $payment->getApprovalLink();
    }

    /**
     * {@inheritdoc}
     */
    public function executePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo)
    {
        try {
            $apiContext = $this->payPalSDKObjectTranslator->getApiContext($apiContextInfo);
            $payment = $this->payPalClient->getPaymentById($paymentInfo->getPaymentId(), $apiContext);

            $payment = $this->doExecute($payment, $paymentInfo, $apiContext);

            $order = $this->getPaymentOrder($payment);
            $paymentInfo->setOrderId($order->getId());
        } catch (PayPalConnectionException $connectionException) {
            $this->processConnectionException($connectionException, $paymentInfo, 'Could not execute payment');
        } catch (\Throwable $exception) {
            $this->processException($exception, 'Could not execute payment');
        }

        if ($payment->getState() != self::PAYMENT_EXECUTED_STATUS) {
            $message = 'Could not executed payment.';

            $this->logger->error(
                $message,
                [
                    'paymentId'      => $paymentInfo->getPaymentId(),
                    'payment state'  => $payment->getState(),
                    'failure reason' => $payment->getFailureReason()
                ]
            );

            $exception = $this->exceptionFactory
                ->createOperationExecutionFailedException($message, $payment->getFailureReason());
            throw $exception;
        }
    }

    /**
     * @param Payment     $payment
     * @param PaymentInfo $paymentInfo
     * @param ApiContext  $apiContext
     *
     * @return Payment
     */
    protected function doExecute(Payment $payment, PaymentInfo $paymentInfo, ApiContext $apiContext)
    {
        $execution = $this->payPalSDKObjectTranslator->getPaymentExecution($paymentInfo);
        $payment = $this->payPalClient->executePayment($payment, $execution, $apiContext);

        return $payment;
    }

    /**
     * @param Payment $payment
     *
     * @return Order
     */
    protected function getPaymentOrder(Payment $payment)
    {
        $transactions = $payment->getTransactions();
        $transaction = reset($transactions);
        $relatedResources = $transaction->getRelatedResources();
        /** @var RelatedResources $relatedResource */
        $relatedResource = reset($relatedResources);

        $order = $relatedResource->getOrder();

        if (!$order instanceof Order) {
            $exception = $this->exceptionFactory
                ->createRuntimeException(
                    sprintf(
                        'Order was not created for payment "%s"',
                        $payment->getId()
                    )
                );
            throw $exception;
        }

        return $order;
    }

    /**
     * {@inheritdoc}
     */
    public function authorizePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo)
    {
        if (!$paymentInfo->getOrderId()) {
            $exception = $this->exceptionFactory->createRuntimeException('Order Id is required.');
            throw $exception;
        }

        try {
            $apiContext = $this->payPalSDKObjectTranslator->getApiContext($apiContextInfo);
            $order = $this->payPalClient->getOrderById($paymentInfo->getOrderId(), $apiContext);
            $authorize = $this->doAuthorize($paymentInfo, $order, $apiContext);
        } catch (PayPalConnectionException $connectionException) {
            $this->processConnectionException($connectionException, $paymentInfo, 'Could not authorize payment');
        } catch (\Throwable $exception) {
            $this->processException($exception, 'Could not authorize payment');
        }

        if ($authorize->getState() != self::ORDER_PAYMENT_AUTHORIZED_STATUS) {
            $message = 'Could not authorize payment.';
            $this->logger->error(
                $message,
                [
                    'paymentId'           => $paymentInfo->getPaymentId(),
                    'authorization state' => $authorize->getState(),
                    'reason code'         => $authorize->getReasonCode(),
                    'valid until'         => $authorize->getValidUntil(),
                ]
            );

            $exception = $this->exceptionFactory
                ->createOperationExecutionFailedException($message, $authorize->getReasonCode());
            throw $exception;
        }
    }

    /**
     * @param PaymentInfo $paymentInfo
     * @param Order       $order
     * @param ApiContext  $apiContext
     *
     * @return Authorization
     */
    protected function doAuthorize(PaymentInfo $paymentInfo, Order $order, ApiContext $apiContext)
    {
        $authorization = $this->payPalSDKObjectTranslator->getAuthorization($paymentInfo);
        return $this->payPalClient->authorizeOrder($order, $authorization, $apiContext);
    }

    /**
     * @param PaymentInfo    $paymentInfo
     * @param ApiContextInfo $apiContextInfo
     */
    public function capturePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo)
    {
        if (!$paymentInfo->getOrderId()) {
            $exception = $this->exceptionFactory->createRuntimeException('Order Id is required.');
            throw $exception;
        }

        try {
            $apiContext = $this->payPalSDKObjectTranslator->getApiContext($apiContextInfo);
            $order = $this->payPalClient->getOrderById($paymentInfo->getOrderId(), $apiContext);
            $capture = $this->doCapture($paymentInfo, $order, $apiContext);
        } catch (PayPalConnectionException $connectionException) {
            $this->processConnectionException($connectionException, $paymentInfo, 'Could not capture payment');
        } catch (\Throwable $exception) {
            $this->processException($exception, 'Could not capture payment');
        }

        if ($capture->getState() != self::ORDER_PAYMENT_CAPTURED_STATUS) {
            $message = 'Could not capture payment.';

            $this->logger->error(
                $message,
                [
                    'paymentId'     => $paymentInfo->getPaymentId(),
                    'capture state' => $capture->getState()
                ]
            );

            $exception = $this->exceptionFactory
                ->createOperationExecutionFailedException($message);
            throw $exception;
        }
    }

    /**
     * Connection exception will be raised on any PayPal API exception by SDK
     *
     * @param PayPalConnectionException $connectionException
     * @param PaymentInfo               $paymentInfo
     * @param string                    $message
     *
     * @throws ConnectionException
     */
    protected function processConnectionException(
        PayPalConnectionException $connectionException,
        PaymentInfo $paymentInfo,
        $message
    ) {
        $exceptionInfo = $this->payPalSDKObjectTranslator->getExceptionInfo($connectionException, $paymentInfo);

        $this->logger->error(
            sprintf(
                '%s. [Reason: %s, Code: %s, Payment Id: %s Details: %s, Informational Link: %s Debug Id: %s].',
                $message,
                $exceptionInfo->getMessage(),
                $exceptionInfo->getStatusCode(),
                $paymentInfo->getPaymentId(),
                $exceptionInfo->getDetails(),
                $exceptionInfo->getRelatedResourceLink(),
                $exceptionInfo->getDebugId()
            ),
            [
                'exception' => $connectionException,
                'exceptionInfo' => $exceptionInfo
            ]
        );

        $exception = $this->exceptionFactory->createConnectionException($message, $exceptionInfo, $connectionException);
        throw $exception;
    }

    /**
     * Internal PHP exception usually not related to API
     *
     * @param \Throwable $exception
     * @param string     $message
     *
     * @throws RuntimeException
     */
    protected function processException(\Throwable $exception, $message)
    {
        $message = sprintf(
            '%s. Reason: %s',
            $message,
            $exception->getMessage()
        );

        $this->logger->error($message, ['exception' => $exception]);

        $exception = $this->exceptionFactory->createRuntimeException($message, $exception);
        throw $exception;
    }

    /**
     * @param PaymentInfo $paymentInfo
     * @param Order       $order
     * @param ApiContext  $apiContext
     *
     * @return Capture
     */
    protected function doCapture(PaymentInfo $paymentInfo, Order $order, ApiContext $apiContext)
    {
        $captureDetails = $this->payPalSDKObjectTranslator->getCapturedDetails($paymentInfo);
        return $this->payPalClient->captureOrder($order, $captureDetails, $apiContext);
    }
}
