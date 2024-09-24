<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ApiContextInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\Context;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\TransportException;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\TransportExceptionFactoryInterface;
use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\Order;
use PayPal\Api\Payment;
use PayPal\Api\RelatedResources;
use PayPal\Rest\ApiContext;

/**
 * Responsible for interaction with {@see PayPalClient} and {@see PayPalSDKObjectTranslatorInterface},
 * also responsible for wrap PayPal SDK exceptions in client code.
 *
 * @see Resources/doc/reference/extension-points.md
 */
class PayPalExpressTransport implements PayPalExpressTransportInterface
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

    public function __construct(
        PayPalSDKObjectTranslatorInterface $translator,
        PayPalClient $payPalClient,
        TransportExceptionFactoryInterface $exceptionFactory
    ) {
        $this->translator = $translator;
        $this->client = $payPalClient;
        $this->exceptionFactory = $exceptionFactory;
    }

    #[\Override]
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
            $context = new Context();
            $context->setPaymentInfo($paymentInfo)->setPayment($payment ?? null);

            throw $this->createTransportException('Create payment failed.', $throwable, $context);
        }

        if ($payment->getState() != self::PAYMENT_CREATED_STATUS) {
            $context = new Context();
            $context->setPaymentInfo($paymentInfo)->setPayment($payment);

            throw $this->createTransportException('Unexpected state of payment after create.', null, $context);
        }

        return $payment->getApprovalLink();
    }

    /**
     * @param string     $message
     * @param \Throwable|null $throwable
     * @param Context    $context
     * @return TransportException
     */
    protected function createTransportException($message, \Throwable $throwable = null, Context $context)
    {
        return $this->exceptionFactory->createTransportException(
            $message,
            $context,
            $throwable
        );
    }

    #[\Override]
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
            $context = new Context();
            $context->setPaymentInfo($paymentInfo)->setPayment($payment ?? null);

            throw $this->createTransportException('Execute payment failed.', $throwable, $context);
        }

        if ($payment->getState() != self::PAYMENT_EXECUTED_STATUS) {
            $context = new Context();
            $context->setPaymentInfo($paymentInfo)->setPayment($payment);

            throw $this->createTransportException('Unexpected state of payment after execute.', null, $context);
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
            $context = new Context();
            $context->setPaymentInfo($paymentInfo)->setPayment($payment);

            throw $this->createTransportException('Order was not created for payment after execute.', null, $context);
        }

        return $order;
    }

    #[\Override]
    public function authorizePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo)
    {
        if (!$paymentInfo->getOrderId()) {
            $context = new Context();
            $context->setPaymentInfo($paymentInfo);

            throw $this->createTransportException('Cannot authorize payment. Order Id is required.', null, $context);
        }

        try {
            $apiContext = $this->translator->getApiContext($apiContextInfo);
            $order = $this->client->getOrderById($paymentInfo->getOrderId(), $apiContext);
            $authorization = $this->doAuthorize($paymentInfo, $order, $apiContext);
        } catch (\Throwable $throwable) {
            $context = new Context();
            $context->setPaymentInfo($paymentInfo);

            throw $this->createTransportException('Payment order authorization failed.', $throwable, $context);
        }

        if ($authorization->getState() != self::ORDER_PAYMENT_AUTHORIZED_STATUS) {
            $context = new Context();
            $context->setPaymentInfo($paymentInfo)->setAuthorization($authorization);

            throw $this->createTransportException('Unexpected state of payment authorization.', null, $context);
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
        $authorization = $this->translator->getAuthorization($paymentInfo);
        return $this->client->authorizeOrder($order, $authorization, $apiContext);
    }

    #[\Override]
    public function capturePayment(PaymentInfo $paymentInfo, ApiContextInfo $apiContextInfo)
    {
        if (!$paymentInfo->getOrderId()) {
            $context = new Context();
            $context->setPaymentInfo($paymentInfo);

            throw $this->createTransportException('Cannot capture payment. Order Id is required.', null, $context);
        }

        try {
            $apiContext = $this->translator->getApiContext($apiContextInfo);
            $order = $this->client->getOrderById($paymentInfo->getOrderId(), $apiContext);
            $capture = $this->doCapture($paymentInfo, $order, $apiContext);
        } catch (\Throwable $throwable) {
            $context = new Context();
            $context->setPaymentInfo($paymentInfo);

            throw $this->createTransportException('Payment capture failed.', $throwable, $context);
        }

        if ($capture->getState() != self::ORDER_PAYMENT_CAPTURED_STATUS) {
            $context = new Context();
            $context->setPaymentInfo($paymentInfo)->setCapture($capture);

            throw $this->createTransportException('Unexpected payment state after capture.', null, $context);
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
        $captureDetails = $this->translator->getCapturedDetails($paymentInfo);
        return $this->client->captureOrder($order, $captureDetails, $apiContext);
    }
}
