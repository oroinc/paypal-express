<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\TransportException;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\TransportExceptionFactoryInterface;

use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\Order;
use PayPal\Api\Payment;
use PayPal\Api\RelatedResources;
use PayPal\Rest\ApiContext;

class PayPalTransport implements PayPalTransportInterface
{
    const PAYMENT_CREATED_STATUS = 'created';
    const PAYMENT_EXECUTED_STATUS = 'approved';
    const ORDER_PAYMENT_AUTHORIZED_STATUS = 'authorized';
    const ORDER_PAYMENT_CAPTURED_STATUS = 'completed';

    /**
     * @var PayPalSDKObjectTranslatorInterface
     */
    protected $translator;

    /**
     * @var PayPalClient
     */
    protected $client;

    /**
     * @var TransportExceptionFactoryInterface
     */
    protected $exceptionFactory;

    /**
     * @param PayPalSDKObjectTranslatorInterface $translator
     * @param PayPalClient                       $payPalClient
     * @param TransportExceptionFactoryInterface $exceptionFactory
     */
    public function __construct(
        PayPalSDKObjectTranslatorInterface $translator,
        PayPalClient $payPalClient,
        TransportExceptionFactoryInterface $exceptionFactory
    ) {
        $this->translator = $translator;
        $this->client = $payPalClient;
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
            $payment = $this->translator->getPayment($paymentInfo, $redirectRoutesInfo);
            $apiContext = $this->translator->getApiContext($apiContextInfo);
            $payment = $this->client->createPayment($payment, $apiContext);

            $paymentInfo->setPaymentId($payment->getId());
        } catch (\Throwable $throwable) {
            throw  $this->createTransportException(
                'Create payment failed.',
                $throwable,
                $this->getPaymentInfoErrorContext($paymentInfo),
                $this->getPaymentErrorContext($payment ?? null)
            );
        }

        if ($payment->getState() != self::PAYMENT_CREATED_STATUS) {
            throw $this->createTransportException(
                'Unexpected state of payment after create.',
                null,
                $this->getPaymentInfoErrorContext($paymentInfo),
                $this->getPaymentErrorContext($payment)
            );
        }

        return $payment->getApprovalLink();
    }


    /**
     * @param string     $message
     * @param \Throwable $throwable
     * @param array      ...$contexts
     * @return TransportException
     */
    protected function createTransportException($message, \Throwable $throwable = null, ...$contexts)
    {
        foreach ($contexts as &$context) {
            $context = array_filter($context);
        }
        $errorContext = array_merge(...$contexts);
        return $this->exceptionFactory->createTransportException(
            $message,
            $errorContext,
            $throwable
        );
    }

    /**
     * @param PaymentInfo|null $paymentInfo
     * @return array
     */
    protected function getPaymentInfoErrorContext(PaymentInfo $paymentInfo = null)
    {
        if (!$paymentInfo) {
            return [];
        }
        return [
            'payment_id' => $paymentInfo->getPaymentId(),
            'order_id'   => $paymentInfo->getOrderId(),
        ];
    }

    /**
     * @param Payment|null $payment
     * @return array
     */
    protected function getPaymentErrorContext(Payment $payment = null)
    {
        if (!$payment) {
            return [];
        }
        return [
            'payment_id'             => $payment->getId(),
            'payment_state'          => $payment->getState(),
            'payment_failure_reason' => $payment->getFailureReason(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function executePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo)
    {
        try {
            $apiContext = $this->translator->getApiContext($apiContextInfo);
            $payment = $this->client->getPaymentById($paymentInfo->getPaymentId(), $apiContext);

            $payment = $this->doExecute($payment, $paymentInfo, $apiContext);

            $order = $this->getPaymentOrder($paymentInfo, $payment);
            $paymentInfo->setOrderId($order->getId());
        } catch (TransportException $exception) {
            throw $exception;
        } catch (\Throwable $throwable) {
            throw $this->createTransportException(
                'Execute payment failed.',
                $throwable,
                $this->getPaymentInfoErrorContext($paymentInfo),
                $this->getPaymentErrorContext($payment ?? null)
            );
        }

        if ($payment->getState() != self::PAYMENT_EXECUTED_STATUS) {
            throw $this->createTransportException(
                'Unexpected state of payment after execute.',
                null,
                $this->getPaymentInfoErrorContext($paymentInfo),
                $this->getPaymentErrorContext($payment)
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
        $execution = $this->translator->getPaymentExecution($paymentInfo);
        $payment = $this->client->executePayment($payment, $execution, $apiContext);

        return $payment;
    }

    /**
     * @param PaymentInfo $paymentInfo
     * @param Payment     $payment
     *
     * @return Order
     */
    protected function getPaymentOrder(PaymentInfo $paymentInfo, Payment $payment)
    {
        $transactions = $payment->getTransactions();
        $transaction = reset($transactions);
        $relatedResources = $transaction->getRelatedResources();
        /** @var RelatedResources $relatedResource */
        $relatedResource = reset($relatedResources);

        $order = $relatedResource->getOrder();

        if (!$order instanceof Order) {
            throw $this->createTransportException(
                'Order was not created for payment after execute.',
                null,
                $this->getPaymentInfoErrorContext($paymentInfo),
                $this->getPaymentErrorContext($payment)
            );
        }

        return $order;
    }

    /**
     * {@inheritdoc}
     */
    public function authorizePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo)
    {
        if (!$paymentInfo->getOrderId()) {
            throw $this->createTransportException(
                'Cannot authorize payment. Order Id is required.',
                null,
                $this->getPaymentInfoErrorContext($paymentInfo)
            );
        }

        try {
            $apiContext = $this->translator->getApiContext($apiContextInfo);
            $order = $this->client->getOrderById($paymentInfo->getOrderId(), $apiContext);
            $authorization = $this->doAuthorize($paymentInfo, $order, $apiContext);
        } catch (\Throwable $throwable) {
            throw $this->createTransportException(
                'Payment order authorization failed.',
                $throwable,
                $this->getPaymentInfoErrorContext($paymentInfo),
                $this->getAuthorizationErrorContext($authorization ?? null)
            );
        }

        if ($authorization->getState() != self::ORDER_PAYMENT_AUTHORIZED_STATUS) {
            throw $this->createTransportException(
                'Unexpected state of payment authorization.',
                null,
                $this->getPaymentInfoErrorContext($paymentInfo),
                $this->getAuthorizationErrorContext($authorization)
            );
        }
    }

    /**
     * @param Authorization|null $authorization
     * @return array
     */
    protected function getAuthorizationErrorContext(Authorization $authorization = null)
    {
        if (!$authorization) {
            return [];
        }
        return [
            'authorization_state'       => $authorization->getState(),
            'authorization_reason_code' => $authorization->getReasonCode(),
            'authorization_valid_until' => $authorization->getValidUntil(),
        ];
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
        $authorization = $this->translator->getAuthorization($paymentInfo);
        return $this->client->authorizeOrder($order, $authorization, $apiContext);
    }

    /**
     * @param PaymentInfo    $paymentInfo
     * @param ApiContextInfo $apiContextInfo
     */
    public function capturePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo)
    {
        if (!$paymentInfo->getOrderId()) {
            throw $this->createTransportException(
                'Cannot capture payment. Order Id is required.',
                null,
                $this->getPaymentInfoErrorContext($paymentInfo)
            );
        }

        try {
            $apiContext = $this->translator->getApiContext($apiContextInfo);
            $order = $this->client->getOrderById($paymentInfo->getOrderId(), $apiContext);
            $capture = $this->doCapture($paymentInfo, $order, $apiContext);
        } catch (\Throwable $throwable) {
            throw $this->createTransportException(
                'Payment capture failed.',
                $throwable,
                $this->getPaymentInfoErrorContext($paymentInfo),
                $this->getCaptureErrorContext($capture ?? null)
            );
        }

        if ($capture->getState() != self::ORDER_PAYMENT_CAPTURED_STATUS) {
            throw $this->createTransportException(
                'Unexpected payment state after capture.',
                null,
                $this->getPaymentInfoErrorContext($paymentInfo),
                $this->getCaptureErrorContext($capture)
            );
        }
    }

    /**
     * @param Capture|null $capture
     * @return array
     */
    protected function getCaptureErrorContext(Capture $capture = null)
    {
        if (!$capture) {
            return [];
        }
        return [
            'parent_payment' => $capture->getParentPayment(),
            'capture_state'  => $capture->getState(),
        ];
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
        $captureDetails = $this->translator->getCapturedDetails($paymentInfo);
        return $this->client->captureOrder($order, $captureDetails, $apiContext);
    }
}
