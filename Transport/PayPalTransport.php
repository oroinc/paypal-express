<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Exception\ConnectionException;
use Oro\Bundle\PayPalExpressBundle\Exception\OperationExecutionFailedException;
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
     * @param PayPalSDKObjectTranslatorInterface $payPalSDKObjectTranslator
     * @param PayPalClient                       $payPalClient
     * @param LoggerInterface                    $logger
     */
    public function __construct(
        PayPalSDKObjectTranslatorInterface $payPalSDKObjectTranslator,
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
        ApiContextInfo $apiContextInfo,
        RedirectRoutesInfo $redirectRoutesInfo
    ) {
        try {
            $payment = $this->payPalSDKObjectTranslator->getPayment($paymentInfo, $redirectRoutesInfo);
            $apiContext = $this->payPalSDKObjectTranslator->getApiContext($apiContextInfo);
            $payment = $this->payPalClient->createPayment($payment, $apiContext);

            $paymentInfo->setPaymentId($payment->getId());
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

            throw new ConnectionException('Could not connect to PayPal server.', 0, $connectionException);
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

            throw new RuntimeException('Could not create payment for PayPal.', 0, $exception);
        }

        if ($payment->getState() != self::PAYMENT_CREATED_STATUS) {
            $this->logger->error(
                'Could not create the payment.',
                [
                    'Payment state'  => $payment->getState(),
                    'Payment id'     => $payment->getId(),
                    'Failure reason' => $payment->getFailureReason(),
                ]
            );

            throw OperationExecutionFailedException::create(
                'Create Payment',
                $paymentInfo->getPaymentId(),
                $payment->getState(),
                $payment->getFailureReason()
            );
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
            $this->logger->error(
                sprintf(
                    'Could not connect to PayPal server. Reason: %s',
                    $connectionException->getMessage()
                ),
                [
                    'exception' => $connectionException
                ]
            );

            throw new ConnectionException('Could not connect to PayPal server.', 0, $connectionException);
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

            throw new RuntimeException('Could not execute payment.', 0, $exception);
        }

        if ($payment->getState() != self::PAYMENT_EXECUTED_STATUS) {
            $this->logger->error(
                'Could not executed payment.',
                [
                    'paymentId'      => $paymentInfo->getPaymentId(),
                    'payment state'  => $payment->getState(),
                    'failure reason' => $payment->getFailureReason()
                ]
            );

            throw OperationExecutionFailedException::create(
                'Payment Execution',
                $paymentInfo->getPaymentId(),
                $payment->getState(),
                $payment->getFailureReason()
            );
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
            throw new RuntimeException(
                sprintf(
                    'Order was not created for payment "%s"',
                    $payment->getId()
                )
            );
        }

        return $order;
    }

    /**
     * @param PaymentInfo    $paymentInfo
     * @param ApiContextInfo $apiContextInfo
     */
    public function authorizePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo)
    {
        if (!$paymentInfo->getOrderId()) {
            throw new RuntimeException('Order Id is required.');
        }

        try {
            $apiContext = $this->payPalSDKObjectTranslator->getApiContext($apiContextInfo);
            $order = $this->payPalClient->getOrderById($paymentInfo->getOrderId(), $apiContext);
            $authorize = $this->doAuthorize($paymentInfo, $order, $apiContext);
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

            throw new ConnectionException('Could not connect to PayPal server.', 0, $connectionException);
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not authorize payment. Reason: %s',
                    $exception->getMessage()
                ),
                [
                    'exception' => $exception
                ]
            );

            throw new RuntimeException('Could not authorize payment.', 0, $exception);
        }

        if ($authorize->getState() != self::ORDER_PAYMENT_AUTHORIZED_STATUS) {
            $this->logger->error(
                'Could not authorize payment.',
                [
                    'paymentId'           => $paymentInfo->getPaymentId(),
                    'authorization state' => $authorize->getState(),
                    'reason code'         => $authorize->getReasonCode(),
                    'valid until'         => $authorize->getValidUntil(),
                    'processor response'  => $authorize->getProcessorResponse()
                ]
            );

            throw OperationExecutionFailedException::create(
                'Payment Authorization',
                $paymentInfo->getPaymentId(),
                $authorize->getState(),
                $authorize->getReasonCode()
            );
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
            throw new RuntimeException('Order Id is required.');
        }

        try {
            $apiContext = $this->payPalSDKObjectTranslator->getApiContext($apiContextInfo);
            $order = $this->payPalClient->getOrderById($paymentInfo->getOrderId(), $apiContext);
            $capture = $this->doCapture($paymentInfo, $order, $apiContext);
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

            throw new ConnectionException('Could not connect to PayPal server.', 0, $connectionException);
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'Could not capture payment. Reason: %s',
                    $exception->getMessage()
                ),
                [
                    'exception' => $exception
                ]
            );

            throw new RuntimeException('Could not capture payment.', 0, $exception);
        }

        if ($capture->getState() != self::ORDER_PAYMENT_CAPTURED_STATUS) {
            $this->logger->error(
                'Could not capture payment.',
                [
                    'paymentId'     => $paymentInfo->getPaymentId(),
                    'capture state' => $capture->getState()
                ]
            );

            throw OperationExecutionFailedException::create(
                'Capture Payment',
                $paymentInfo->getPaymentId(),
                $capture->getState()
            );
        }
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
