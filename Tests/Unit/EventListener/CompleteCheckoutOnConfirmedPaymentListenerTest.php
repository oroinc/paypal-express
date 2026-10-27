<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\TransactionCompleteEvent;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PayPalExpressBundle\EventListener\CompleteCheckoutOnConfirmedPaymentListener;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompleteCheckoutOnConfirmedPaymentListenerTest extends TestCase
{
    private const WORKFLOW_RECORD_GROUP = 'b2b_checkout_flow';
    private const WORKFLOW_NAME = 'b2b_flow_checkout';
    private const FINISH_CHECKOUT_TRANSITION = 'finish_checkout';
    private const PAYMENT_METHOD = 'oro_paypal_express_1';

    private PaymentMethodProviderInterface|MockObject $paymentMethodProvider;
    private ManagerRegistry|MockObject $managerRegistry;
    private PaymentStatusManager|MockObject $paymentStatusManager;
    private WorkflowManager|MockObject $workflowManager;
    private CompleteCheckoutOnConfirmedPaymentListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->paymentStatusManager = $this->createMock(PaymentStatusManager::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->listener = new CompleteCheckoutOnConfirmedPaymentListener(
            $this->paymentMethodProvider,
            $this->managerRegistry,
            $this->paymentStatusManager,
            $this->workflowManager
        );
    }

    private function createTransaction(
        string $entityClass = Order::class,
        int $entityId = 42,
        bool $successful = true,
        bool $active = false,
        string $paymentMethod = self::PAYMENT_METHOD
    ): PaymentTransaction {
        $transaction = new PaymentTransaction();
        $transaction->setEntityClass($entityClass);
        $transaction->setEntityIdentifier($entityId);
        $transaction->setSuccessful($successful);
        $transaction->setActive($active);
        $transaction->setPaymentMethod($paymentMethod);

        return $transaction;
    }

    private function mockPaymentMethodProvider(
        string $paymentMethod = self::PAYMENT_METHOD,
        bool $hasMethod = true
    ): void {
        $this->paymentMethodProvider->expects(self::once())
            ->method('hasPaymentMethod')
            ->with($paymentMethod)
            ->willReturn($hasMethod);
    }

    private function mockManagerRegistry(Order $order): void
    {
        $orderManager = $this->createConfiguredMock(ObjectManager::class, ['find' => $order]);
        $this->managerRegistry->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($orderManager);
    }

    private function mockCheckoutRepository(Order $order, ?Checkout $checkout): void
    {
        $checkoutRepository = $this->createMock(ObjectRepository::class);
        $checkoutRepository->method('findOneBy')
            ->with(['order' => $order, 'deleted' => false])
            ->willReturn($checkout);
        $this->managerRegistry->method('getRepository')
            ->with(Checkout::class)
            ->willReturn($checkoutRepository);
    }

    private function mockPaymentStatus(Order $order, string $status): void
    {
        $this->paymentStatusManager->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn((new PaymentStatus())->setPaymentStatus($status));
    }

    private function mockWorkflowItem(Checkout $checkout, ?WorkflowItem $workflowItem): void
    {
        $workflow = $this->createConfiguredMock(Workflow::class, ['getName' => self::WORKFLOW_NAME]);
        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, self::WORKFLOW_RECORD_GROUP)
            ->willReturn($workflow);
        $this->workflowManager->expects(self::once())
            ->method('getWorkflowItem')
            ->with($checkout, self::WORKFLOW_NAME)
            ->willReturn($workflowItem);
    }

    private function expectNoLookups(): void
    {
        $this->managerRegistry->expects(self::never())->method('getManagerForClass');
        $this->managerRegistry->expects(self::never())->method('getRepository');
        $this->paymentStatusManager->expects(self::never())->method('getPaymentStatus');
        $this->workflowManager->expects(self::never())->method('transitIfAllowed');
    }

    public function testDoesNothingWhenTransactionIsNotForAnOrder(): void
    {
        $this->paymentMethodProvider->expects(self::never())->method('hasPaymentMethod');
        $this->expectNoLookups();

        $this->listener->onTransactionComplete(
            new TransactionCompleteEvent($this->createTransaction(entityClass: Checkout::class))
        );
    }

    public function testDoesNothingWhenTransactionIsNotSuccessful(): void
    {
        $this->paymentMethodProvider->expects(self::never())->method('hasPaymentMethod');
        $this->expectNoLookups();

        $this->listener->onTransactionComplete(
            new TransactionCompleteEvent($this->createTransaction(successful: false))
        );
    }

    public function testDrivesTheFinishCheckoutTransitionForActiveAuthorizeTransaction(): void
    {
        $this->mockPaymentMethodProvider();

        $order = new Order();
        $checkout = (new Checkout())->setOrder($order)->setPaymentInProgress(true);
        $this->mockManagerRegistry($order);
        $this->mockCheckoutRepository($order, $checkout);
        $this->mockPaymentStatus($order, PaymentStatuses::AUTHORIZED);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->mockWorkflowItem($checkout, $workflowItem);

        $this->workflowManager->expects(self::once())
            ->method('transitIfAllowed')
            ->with($workflowItem, self::FINISH_CHECKOUT_TRANSITION);

        $transaction = $this->createTransaction(active: true);
        $transaction->setAction(PaymentMethodInterface::AUTHORIZE);

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($transaction));
    }

    public function testDoesNothingWhenPaymentMethodIsNotPayPalExpress(): void
    {
        $this->mockPaymentMethodProvider('any_other_payment_method', false);
        $this->expectNoLookups();

        $this->listener->onTransactionComplete(
            new TransactionCompleteEvent($this->createTransaction(paymentMethod: 'any_other_payment_method'))
        );
    }

    public function testDoesNothingWhenOrderIsNotFound(): void
    {
        $this->mockPaymentMethodProvider();

        $orderManager = $this->createMock(ObjectManager::class);
        $orderManager->expects(self::once())
            ->method('find')
            ->with(Order::class, 42)
            ->willReturn(null);

        $this->managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($orderManager);
        $this->managerRegistry->expects(self::never())->method('getRepository');
        $this->paymentStatusManager->expects(self::never())->method('getPaymentStatus');
        $this->workflowManager->expects(self::never())->method('transitIfAllowed');

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($this->createTransaction()));
    }

    public function testDoesNothingWhenNoCheckoutIsLinkedToTheOrder(): void
    {
        $this->mockPaymentMethodProvider();

        $order = new Order();
        $this->mockManagerRegistry($order);
        $this->mockCheckoutRepository($order, null);

        // Not calling getPaymentStatus avoids upserting a PaymentStatus row as a side effect
        // when there's nothing to complete.
        $this->paymentStatusManager->expects(self::never())->method('getPaymentStatus');
        $this->workflowManager->expects(self::never())->method('transitIfAllowed');

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($this->createTransaction()));
    }

    public function testDoesNothingWhenCheckoutIsAlreadyCompleted(): void
    {
        $this->mockPaymentMethodProvider();

        $order = new Order();
        $checkout = (new Checkout())->setOrder($order)->setCompleted(true);
        $this->mockManagerRegistry($order);
        $this->mockCheckoutRepository($order, $checkout);

        $this->paymentStatusManager->expects(self::never())->method('getPaymentStatus');
        $this->workflowManager->expects(self::never())->method('transitIfAllowed');

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($this->createTransaction()));
    }

    /**
     * @dataProvider nonPaidStatusDataProvider
     */
    public function testDoesNothingWhenOrderIsNotPaid(string $paymentStatus): void
    {
        $this->mockPaymentMethodProvider();

        $order = new Order();
        $checkout = (new Checkout())->setOrder($order)->setPaymentInProgress(true);
        $this->mockManagerRegistry($order);
        $this->mockCheckoutRepository($order, $checkout);
        $this->mockPaymentStatus($order, $paymentStatus);

        $this->workflowManager->expects(self::never())->method('getAvailableWorkflowByRecordGroup');
        $this->workflowManager->expects(self::never())->method('transitIfAllowed');

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($this->createTransaction()));

        self::assertTrue($checkout->isPaymentInProgress(), 'A non-paid transaction must not clear the flag.');
        self::assertFalse($checkout->isCompleted());
    }

    public function nonPaidStatusDataProvider(): array
    {
        return [
            'pending' => [PaymentStatuses::PENDING],
            'declined' => [PaymentStatuses::DECLINED],
            'canceled' => [PaymentStatuses::CANCELED],
            'canceled partially' => [PaymentStatuses::CANCELED_PARTIALLY],
            'invoiced' => [PaymentStatuses::INVOICED],
            'refunded' => [PaymentStatuses::REFUNDED],
            'refunded partially' => [PaymentStatuses::REFUNDED_PARTIALLY],
        ];
    }

    /**
     * @dataProvider paidStatusDataProvider
     */
    public function testDrivesTheFinishCheckoutTransitionWhenAWorkflowItemExists(string $paymentStatus): void
    {
        $this->mockPaymentMethodProvider();

        $order = new Order();
        $checkout = (new Checkout())->setOrder($order)->setPaymentInProgress(true);
        $this->mockManagerRegistry($order);
        $this->mockCheckoutRepository($order, $checkout);
        $this->mockPaymentStatus($order, $paymentStatus);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $this->mockWorkflowItem($checkout, $workflowItem);

        $this->workflowManager->expects(self::once())
            ->method('transitIfAllowed')
            ->with($workflowItem, self::FINISH_CHECKOUT_TRANSITION);

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($this->createTransaction()));
    }

    public function paidStatusDataProvider(): array
    {
        return [
            'paid in full' => [PaymentStatuses::PAID_IN_FULL],
            'paid partially' => [PaymentStatuses::PAID_PARTIALLY],
            'authorized' => [PaymentStatuses::AUTHORIZED],
            'authorized partially' => [PaymentStatuses::AUTHORIZED_PARTIALLY],
        ];
    }

    public function testDoesNothingWhenNoWorkflowDefinitionIsFound(): void
    {
        $this->mockPaymentMethodProvider();

        $order = new Order();
        $checkout = (new Checkout())->setOrder($order);
        $this->mockManagerRegistry($order);
        $this->mockCheckoutRepository($order, $checkout);
        $this->mockPaymentStatus($order, PaymentStatuses::PAID_IN_FULL);

        $this->workflowManager->expects(self::once())
            ->method('getAvailableWorkflowByRecordGroup')
            ->with(Checkout::class, self::WORKFLOW_RECORD_GROUP)
            ->willReturn(null);
        $this->workflowManager->expects(self::never())->method('getWorkflowItem');
        $this->workflowManager->expects(self::never())->method('transitIfAllowed');

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($this->createTransaction()));

        self::assertFalse($checkout->isCompleted());
    }

    public function testDoesNothingWhenWorkflowExistsButItemDoesNot(): void
    {
        $this->mockPaymentMethodProvider();

        $order = new Order();
        $checkout = (new Checkout())->setOrder($order);
        $this->mockManagerRegistry($order);
        $this->mockCheckoutRepository($order, $checkout);
        $this->mockPaymentStatus($order, PaymentStatuses::PAID_IN_FULL);
        $this->mockWorkflowItem($checkout, null);

        $this->workflowManager->expects(self::never())->method('transitIfAllowed');

        $this->listener->onTransactionComplete(new TransactionCompleteEvent($this->createTransaction()));

        self::assertFalse($checkout->isCompleted());
    }
}
