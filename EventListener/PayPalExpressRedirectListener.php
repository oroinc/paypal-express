<?php

namespace Oro\Bundle\PayPalExpressBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CompleteVirtualAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionResponseData;
use Psr\Log\LoggerAwareTrait;

/**
 * Handles a payment callback event triggered when PayPal redirects a user after an attempt to make a payment.
 * If payment was created successfully in PayPal, then a {@see CompleteVirtualAction} action will be executed.
 * Otherwise, a payment transaction marked as failed.
 */
class PayPalExpressRedirectListener
{
    use LoggerAwareTrait;

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

    public function __construct(PaymentMethodProviderInterface $paymentMethodProvider)
    {
        $this->paymentMethodProvider = $paymentMethodProvider;
    }

    public function onError(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
            return;
        }

        if (!$paymentTransaction->isActive()) {
            return;
        }

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
            return;
        }

        $paymentTransaction
            ->setSuccessful(false)
            ->setActive(false);
    }

    public function onReturn(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
            return;
        }

        if (!$paymentTransaction->isActive()) {
            return;
        }

        $paymentMethodId = $paymentTransaction->getPaymentMethod();

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentMethodId)) {
            return;
        }

        $eventData = $event->getData();

        if (!$paymentTransaction || !isset($eventData['paymentId'], $eventData['PayerID'], $eventData['token']) ||
            $eventData['paymentId'] !== $paymentTransaction->getReference()
        ) {
            return;
        }

        $response = $paymentTransaction->getResponse();
        $response[PaymentTransactionResponseData::PAYMENT_ID_FIELD_KEY] = $eventData['paymentId'];
        $response[PaymentTransactionResponseData::PAYER_ID_FIELD_KEY] = $eventData['PayerID'];

        $paymentTransaction->setResponse($response);

        try {
            $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($paymentMethodId);
            $paymentMethod->execute(CompleteVirtualAction::NAME, $paymentTransaction);

            $event->markSuccessful();
        } catch (\InvalidArgumentException $e) {
            if ($this->logger) {
                // do not expose sensitive data in context
                $this->logger->error($e->getMessage(), []);
            }
        }
    }
}
