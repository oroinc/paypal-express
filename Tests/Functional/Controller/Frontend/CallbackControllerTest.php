<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\LoadShoppingListsCheckoutsData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\PayPalExpressBundle\Tests\Functional\Environment\Transport\PayPalClientMock;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Drives the real `oro_payment_callback_return` route to prove
 * Oro\Bundle\PayPalExpressBundle\EventListener\PayPalExpressRedirectListener::onReturn() persists a payment
 * transaction it fails to complete. The listener stops the event propagation on this path, so
 * CallbackHandler::handle() never gets to save the transaction itself - without the listener's own
 * savePaymentTransaction() call, the failed state would only ever exist in memory.
 *
 * @dbIsolationPerTest
 */
class CallbackControllerTest extends WebTestCase
{
    private ManagerRegistry $managerRegistry;
    private string $payPalExpressPaymentMethod;

    protected function setUp(): void
    {
        $this->initClient();

        $this->managerRegistry = self::getContainer()->get('doctrine');

        $this->loadFixtures([LoadShoppingListsCheckoutsData::class, LoadChannelData::class]);

        $this->payPalExpressPaymentMethod = self::getContainer()
            ->get('oro_paypal_express.method.generator.identifier')
            ->generateIdentifier($this->getReference('oro_paypal_express.channel.foo'));
    }

    public function testReturnCallbackPersistsFailedTransactionWhenCompleteActionFails(): void
    {
        $paymentTransaction = $this->createActivePaymentTransaction();

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_payment_callback_return',
                ['accessIdentifier' => $paymentTransaction->getAccessIdentifier()]
            ),
            [
                'paymentId' => $paymentTransaction->getReference(),
                'PayerID' => 'PAYER-TEST-ID',
                'token' => 'TOKEN-TEST-ID',
            ]
        );

        $response = $this->client->getResponse();
        $this->assertNotNull($response);
        $this->assertResponseStatusCodeEquals($response, 302);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://example.com/paypal-express-failure', $response->getTargetUrl());

        $reloadedTransaction = $this->reloadPaymentTransaction($paymentTransaction->getId());
        $this->assertFalse($reloadedTransaction->isActive());
        $this->assertFalse($reloadedTransaction->isSuccessful());
    }

    private function createActivePaymentTransaction(): PaymentTransaction
    {
        // A fresh order with no payment history of its own, so CheckCallbackRelevanceListener (which runs
        // before this listener and checks the order's payment status) sees it as PENDING and does not interfere.
        $order = $this->createOrderWithNoPaymentHistory();

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setAction(PaymentMethodInterface::AUTHORIZE);
        $paymentTransaction->setAmount($order->getTotal());
        $paymentTransaction->setCurrency($order->getCurrency());
        $paymentTransaction->setEntityIdentifier($order->getId());
        $paymentTransaction->setEntityClass(Order::class);
        $paymentTransaction->setPaymentMethod($this->payPalExpressPaymentMethod);
        // Forces PayPalClientMock::getPaymentById() to throw, simulating a PayPal API failure during the
        // post-redirect "complete" step - see PayPalClientMock::FAILING_PAYMENT_ID.
        $paymentTransaction->setReference(PayPalClientMock::FAILING_PAYMENT_ID);
        $paymentTransaction->setActive(true);
        $paymentTransaction->setSuccessful(false);
        $paymentTransaction->setTransactionOptions([
            'failureUrl' => 'https://example.com/paypal-express-failure',
        ]);

        $em = $this->managerRegistry->getManagerForClass(PaymentTransaction::class);
        $em->persist($paymentTransaction);
        $em->flush();

        return $paymentTransaction;
    }

    private function createOrderWithNoPaymentHistory(): Order
    {
        /** @var Checkout $anyCheckout */
        $anyCheckout = $this->getReference(LoadShoppingListsCheckoutsData::CHECKOUT_1);

        $order = new Order();
        $order->setOwner($anyCheckout->getOwner());
        $order->setOrganization($anyCheckout->getOrganization());
        $order->setWebsite($anyCheckout->getWebsite());
        $order->setCustomer($anyCheckout->getCustomerUser()->getCustomer());
        $order->setCustomerUser($anyCheckout->getCustomerUser());
        $order->setIdentifier('BB-27826-paypal-express-return-failure');
        $order->setCurrency('USD');
        $order->setSubtotal(100.0);
        $order->setTotal(100.0);

        $em = $this->managerRegistry->getManagerForClass(Order::class);
        $em->persist($order);
        $em->flush();

        return $order;
    }

    private function reloadPaymentTransaction(int $id): PaymentTransaction
    {
        $em = $this->managerRegistry->getManagerForClass(PaymentTransaction::class);
        $em->clear();

        /** @var PaymentTransaction $transaction */
        $transaction = $em->find(PaymentTransaction::class, $id);
        self::assertNotNull($transaction);

        return $transaction;
    }
}
