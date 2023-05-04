<?php

namespace Oro\Bundle\PayPalExpressBundle\EventListener;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentResultMessageProviderInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CompleteVirtualAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionResponseData;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles a payment callback event triggered when PayPal redirects a user after an attempt to make a payment.
 * If payment was created successfully in PayPal, then a {@see CompleteVirtualAction} action will be executed.
 * Otherwise, a payment transaction marked as failed.
 */
class PayPalExpressRedirectListener
{
    use LoggerAwareTrait;

    public function __construct(
        protected PaymentMethodProviderInterface $paymentMethodProvider,
        protected PaymentResultMessageProviderInterface $messageProvider,
        protected RequestStack $requestStack,
    ) {
    }

    public function onError(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();
        if (!$this->isPaymentTransactionSupport($paymentTransaction)) {
            return;
        }

        $paymentTransaction
            ->setSuccessful(false)
            ->setActive(false);
    }

    public function onReturn(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();
        if (!$this->isPaymentTransactionSupport($paymentTransaction)) {
            return;
        }

        $eventData = $event->getData();
        $paymentMethodId = $paymentTransaction->getPaymentMethod();

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
            $paymentData = $paymentMethod->execute(CompleteVirtualAction::NAME, $paymentTransaction);
            if ($this->isPaymentComplete($paymentData)) {
                $event->markSuccessful();

                return;
            }

            $this->redirectToFailureUrl($paymentTransaction, $event);
        } catch (\InvalidArgumentException $e) {
            if ($this->logger) {
                // do not expose sensitive data in context
                $this->logger->error($e->getMessage(), []);
            }
        }
    }

    /**
     * @param mixed $paymentData
     *
     * @return bool
     */
    private function isPaymentComplete($paymentData): bool
    {
        return is_array($paymentData) && array_key_exists('successful', $paymentData) && $paymentData['successful'];
    }

    private function isPaymentTransactionSupport(?PaymentTransaction $paymentTransaction): bool
    {
        if (!$paymentTransaction) {
            return false;
        }

        if (!$paymentTransaction->isActive()) {
            return false;
        }

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
            return false;
        }

        return true;
    }

    private function redirectToFailureUrl(PaymentTransaction $paymentTransaction, AbstractCallbackEvent $event): void
    {
        $event->stopPropagation();
        $this->setErrorMessage($this->messageProvider->getErrorMessage($paymentTransaction));
        $transactionOptions = $paymentTransaction->getTransactionOptions();
        if (!empty($transactionOptions['failureUrl'])) {
            $event->setResponse(new RedirectResponse($transactionOptions['failureUrl']));
        } else {
            $event->markFailed();
        }
    }

    protected function setErrorMessage(string $message): void
    {
        $flashBag = $this->requestStack->getSession()->getFlashBag();

        if (!$flashBag->has('error')) {
            $flashBag->add('error', $message);
        }
    }
}
