<?php

namespace Oro\Bundle\PayPalExpressBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Completes a checkout as soon as its PayPal Express order payment is confirmed, instead of waiting for the
 * browser to reach `finish_checkout` - the second request PayPal Express never gets back to when its redirect
 * callback is the only round-trip that happens, leaving a genuinely paid order exposed to detachment on a
 * later checkout restart. Other payment methods are left untouched by this listener.
 *
 * The order payment status, not the transaction's active flag, decides whether the payment is confirmed:
 * a successful "authorize" transaction stays active until it is captured.
 *
 * Drives the real `finish_checkout` transition through the workflow engine (step advance, workflow events,
 * audit record) rather than replaying its business logic in isolation. If the checkout has no WorkflowItem,
 * or the transition isn't allowed from its current step, this is a no-op - the checkout is left for
 * whatever normally handles it in that case.
 */
class CompleteCheckoutOnConfirmedPaymentListener
{
    private const CHECKOUT_WORKFLOW_RECORD_GROUP = 'b2b_checkout_flow';
    private const FINISH_CHECKOUT_TRANSITION = 'finish_checkout';

    public function __construct(
        private PaymentMethodProviderInterface $paymentMethodProvider,
        private ManagerRegistry $managerRegistry,
        private PaymentStatusManager $paymentStatusManager,
        private WorkflowManager $workflowManager
    ) {
    }

    public function onTransactionComplete(TransactionCompleteEvent $event): void
    {
        $paymentTransaction = $event->getTransaction();
        if (!$this->isEligibleTransaction($paymentTransaction)) {
            return;
        }

        $order = $this->managerRegistry->getManagerForClass(Order::class)
            ->find(Order::class, $paymentTransaction->getEntityIdentifier());
        if (!$order) {
            return;
        }

        $checkout = $this->managerRegistry->getRepository(Checkout::class)
            ->findOneBy(['order' => $order, 'deleted' => false]);
        if (!$checkout || $checkout->isCompleted() || !$this->isOrderPaid($order)) {
            return;
        }

        $workflowItem = $this->getWorkflowItem($checkout);
        if (!$workflowItem) {
            return;
        }

        $this->workflowManager->transitIfAllowed($workflowItem, self::FINISH_CHECKOUT_TRANSITION);
    }

    private function isEligibleTransaction(PaymentTransaction $paymentTransaction): bool
    {
        return $paymentTransaction->getEntityClass() === Order::class
            && $paymentTransaction->isSuccessful()
            && $this->paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod());
    }

    private function isOrderPaid(Order $order): bool
    {
        $paymentStatus = (string) $this->paymentStatusManager->getPaymentStatus($order);

        return in_array($paymentStatus, [
            PaymentStatuses::PAID_IN_FULL,
            PaymentStatuses::PAID_PARTIALLY,
            PaymentStatuses::AUTHORIZED,
            PaymentStatuses::AUTHORIZED_PARTIALLY,
        ], true);
    }

    private function getWorkflowItem(Checkout $checkout): ?WorkflowItem
    {
        $workflow = $this->workflowManager->getAvailableWorkflowByRecordGroup(
            Checkout::class,
            self::CHECKOUT_WORKFLOW_RECORD_GROUP
        );
        if (!$workflow) {
            return null;
        }

        return $this->workflowManager->getWorkflowItem($checkout, $workflow->getName());
    }
}
