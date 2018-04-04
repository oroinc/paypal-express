<?php

namespace Oro\Bundle\PayPalExpressBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CompleteVirtualAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentTransaction\PaymentTransactionResponseData;
use Psr\Log\LoggerAwareTrait;

/**
 * Responsible for run appropriate payment action after PayPal will redirect user to one of our
 * routes. If it will be success route, configured Complete Action will be executed, if it will be failed route
 * transaction status will be updated to false
 */
class PayPalExpressRedirectListener
{
    use LoggerAwareTrait;

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

    /**
     * @param PaymentMethodProviderInterface $paymentMethodProvider
     */
    public function __construct(PaymentMethodProviderInterface $paymentMethodProvider)
    {
        $this->paymentMethodProvider = $paymentMethodProvider;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onError(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
            return;
        }

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
            return;
        }

        $paymentTransaction
            ->setSuccessful(false)
            ->setActive(false);
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onReturn(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
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
