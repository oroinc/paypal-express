<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PurchaseAction;
use Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Saves a real PaymentTransaction through the real container (the same call the PayPal Express callback makes)
 * and asserts the resulting checkout/order database state - proving
 * Oro\Bundle\PayPalExpressBundle\EventListener\CompleteCheckoutOnConfirmedPaymentListener actually drives the
 * real `finish_checkout` transition on payment confirmation without any `finish_checkout` request, which the
 * mocked unit test cannot, and that it is scoped to PayPal Express transactions only.
 *
 * @dbIsolationPerTest
 */
class CompleteCheckoutOnConfirmedPaymentListenerTest extends WebTestCase
{
    private ManagerRegistry $managerRegistry;
    private WorkflowManager $workflowManager;
    private PaymentTransactionProvider $paymentTransactionProvider;
    private string $payPalExpressPaymentMethod;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->managerRegistry = self::getContainer()->get('doctrine');
        $this->workflowManager = self::getContainer()->get('oro_workflow.manager');
        $this->paymentTransactionProvider = self::getContainer()->get('oro_payment.provider.payment_transaction');

        $this->loadFixtures([LoadShoppingListsCheckoutsData::class, LoadChannelData::class]);

        $this->payPalExpressPaymentMethod = self::getContainer()
            ->get('oro_paypal_express.method.generator.identifier')
            ->generateIdentifier($this->getReference('oro_paypal_express.channel.foo'));
    }

    public function testCheckoutIsCompletedWhenPayPalExpressPaymentIsConfirmedWithoutAnyFinishCheckoutRequest(): void
    {
        [$order, $checkout] = $this->prepareCheckoutWithOrder(LoadShoppingListsCheckoutsData::CHECKOUT_1);
        $checkoutId = $checkout->getId();

        $this->saveTransaction($order, $this->payPalExpressPaymentMethod, successful: true, active: false);

        $reloadedCheckout = $this->reloadCheckout($checkoutId);

        self::assertTrue($reloadedCheckout->isCompleted());
        self::assertFalse($reloadedCheckout->isPaymentInProgress());
        self::assertSame($order->getId(), $reloadedCheckout->getOrder()?->getId());
    }

    public function testCheckoutIsNotCompletedWhenPaymentMethodIsNotPayPalExpress(): void
    {
        [$order, $checkout] = $this->prepareCheckoutWithOrder(LoadShoppingListsCheckoutsData::CHECKOUT_2);
        $checkoutId = $checkout->getId();

        $this->saveTransaction($order, 'any_other_payment_method', successful: true, active: false);

        $reloadedCheckout = $this->reloadCheckout($checkoutId);

        self::assertFalse($reloadedCheckout->isCompleted());
        self::assertTrue($reloadedCheckout->isPaymentInProgress());
    }

    /**
     * The "Authorize" payment action leaves a successful transaction active until it is captured.
     */
    public function testCheckoutIsCompletedWhenPayPalExpressPaymentIsAuthorized(): void
    {
        [$order, $checkout] = $this->prepareCheckoutWithOrder(LoadShoppingListsCheckoutsData::CHECKOUT_3);
        $checkoutId = $checkout->getId();

        $this->saveTransaction(
            $order,
            $this->payPalExpressPaymentMethod,
            successful: true,
            active: true,
            action: PaymentMethodInterface::AUTHORIZE
        );

        $reloadedCheckout = $this->reloadCheckout($checkoutId);

        self::assertTrue($reloadedCheckout->isCompleted());
        self::assertFalse($reloadedCheckout->isPaymentInProgress());
        self::assertSame($order->getId(), $reloadedCheckout->getOrder()?->getId());
    }

    /**
     * The "create_payment" transaction is saved active before the redirect to PayPal; the order is still pending.
     */
    public function testCheckoutIsNotCompletedWhenPayPalExpressPaymentIsPending(): void
    {
        [$order, $checkout] = $this->prepareCheckoutWithOrder(LoadShoppingListsCheckoutsData::CHECKOUT_3);
        $checkoutId = $checkout->getId();

        $this->saveTransaction(
            $order,
            $this->payPalExpressPaymentMethod,
            successful: true,
            active: true,
            action: PurchaseAction::PAYMENT_TRANSACTION_ACTION_NAME
        );

        $reloadedCheckout = $this->reloadCheckout($checkoutId);

        self::assertFalse($reloadedCheckout->isCompleted());
        self::assertTrue($reloadedCheckout->isPaymentInProgress());
    }

    public function testCheckoutIsNotCompletedWhenTransactionFailed(): void
    {
        [$order, $checkout] = $this->prepareCheckoutWithOrder(LoadShoppingListsCheckoutsData::CHECKOUT_4);
        $checkoutId = $checkout->getId();

        $this->saveTransaction($order, $this->payPalExpressPaymentMethod, successful: false, active: false);

        $reloadedCheckout = $this->reloadCheckout($checkoutId);

        self::assertFalse($reloadedCheckout->isCompleted());
        self::assertTrue($reloadedCheckout->isPaymentInProgress());
    }

    /**
     * Documents the deliberate scope decision: a checkout whose WorkflowItem isn't (or is no longer) sitting
     * on a step that allows `finish_checkout` - e.g. moved to `enter_payment` by a `payment_error` transition
     * racing a delayed/duplicate payment confirmation - is left untouched by this listener. It does not force
     * completion outside the workflow engine.
     */
    public function testCheckoutIsNotCompletedWhenCurrentStepDoesNotAllowFinishCheckout(): void
    {
        [$order, $checkout] = $this->prepareCheckoutWithOrder(
            LoadShoppingListsCheckoutsData::CHECKOUT_7,
            'enter_payment'
        );
        $checkoutId = $checkout->getId();

        $this->saveTransaction($order, $this->payPalExpressPaymentMethod, successful: true, active: false);

        $reloadedCheckout = $this->reloadCheckout($checkoutId);

        self::assertFalse($reloadedCheckout->isCompleted());
    }

    public function testPaidOrderWithNoLinkedCheckoutIsIgnored(): void
    {
        $order = $this->createOrder('BB-27826-no-checkout');

        // No exception, nothing to complete - this is a back-office/API order never tied to a checkout.
        $this->saveTransaction($order, $this->payPalExpressPaymentMethod, successful: true, active: false);

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->clear();
        self::assertNotNull($em->find(Order::class, $order->getId()));
    }

    /**
     * @return array{0: Order, 1: Checkout}
     */
    private function prepareCheckoutWithOrder(string $checkoutReference, string $step = 'order_review'): array
    {
        /** @var Checkout $checkout */
        $checkout = $this->getReference($checkoutReference);
        $checkout->setPaymentInProgress(true);
        // This fixture's checkout carries no address; actualizeAddresses() would otherwise dereference it.
        $checkout->setSaveBillingAddress(false);
        $checkout->setSaveShippingAddress(false);

        $order = $this->createOrder('BB-27826-confirmed-payment-' . $checkoutReference);

        $checkout->setOrder($order);
        $this->managerRegistry->getManagerForClass(Checkout::class)->flush();

        $workflowItem = $this->workflowManager->getWorkflowItem($checkout, 'b2b_flow_checkout');
        self::assertNotNull($workflowItem, 'Fixture must have started the b2b_flow_checkout workflow.');

        // The fixture only starts the workflow at its initial step (enter_billing_address); place_order
        // (step_to: order_review) is what a real checkout goes through before payment ever starts, so land
        // the WorkflowItem there directly instead of replaying the whole wizard.
        $workflowStep = $this->managerRegistry->getRepository(WorkflowStep::class)
            ->findOneBy(['definition' => $workflowItem->getDefinition(), 'name' => $step]);
        self::assertNotNull($workflowStep, sprintf('Workflow step "%s" must exist.', $step));
        $workflowItem->setCurrentStep($workflowStep);
        $this->managerRegistry->getManagerForClass(WorkflowItem::class)->flush();

        return [$order, $checkout];
    }

    private function createOrder(string $identifier): Order
    {
        /** @var Checkout $anyCheckout */
        $anyCheckout = $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1);

        $order = new Order();
        $order->setOwner($anyCheckout->getOwner());
        $order->setOrganization($anyCheckout->getOrganization());
        $order->setWebsite($anyCheckout->getWebsite());
        $order->setCustomer($anyCheckout->getCustomerUser()->getCustomer());
        $order->setCustomerUser($anyCheckout->getCustomerUser());
        $order->setPoNumber($identifier);
        $order->setCurrency('USD');
        $order->setSubtotal(100.0);
        $order->setTotal(100.0);
        $order->setIdentifier($identifier);

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        return $order;
    }

    private function saveTransaction(
        Order $order,
        string $paymentMethod,
        bool $successful,
        bool $active,
        string $action = PaymentMethodInterface::CAPTURE
    ): void {
        $transaction = new PaymentTransaction();
        $transaction->setEntityClass(Order::class);
        $transaction->setEntityIdentifier($order->getId());
        $transaction->setAction($action);
        $transaction->setAmount($order->getTotal());
        $transaction->setCurrency($order->getCurrency());
        $transaction->setActive($active);
        $transaction->setSuccessful($successful);
        $transaction->setPaymentMethod($paymentMethod);
        $this->paymentTransactionProvider->savePaymentTransaction($transaction);
    }

    private function reloadCheckout(int $checkoutId): Checkout
    {
        $em = $this->managerRegistry->getManagerForClass(Checkout::class);
        $em->clear();

        /** @var Checkout $checkout */
        $checkout = $em->find(Checkout::class, $checkoutId);
        self::assertNotNull($checkout);

        return $checkout;
    }
}
