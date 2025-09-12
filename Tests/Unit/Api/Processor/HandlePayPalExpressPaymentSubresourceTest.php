<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorMetaProperty;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\FlushDataHandlerInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\SaveParentEntity;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\ChangeSubresourceProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\SplitOrderActionsInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PayPalExpressBundle\Api\Model\PayPalExpressPaymentRequest;
use Oro\Bundle\PayPalExpressBundle\Api\Processor\HandlePayPalExpressPaymentSubresource;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PropertyAccess\PropertyPath;

class HandlePayPalExpressPaymentSubresourceTest extends ChangeSubresourceProcessorTestCase
{
    private SplitOrderActionsInterface&MockObject $splitOrderActions;
    private CheckoutActionsInterface&MockObject $checkoutActions;
    private AddressActionsInterface&MockObject $addressActions;
    private ActionExecutor&MockObject $actionExecutor;
    private PaymentStatusManager&MockObject $paymentStatusManager;
    private GroupedCheckoutLineItemsProvider&MockObject $groupedCheckoutLineItemsProvider;
    private DoctrineHelper&MockObject $doctrineHelper;
    private FlushDataHandlerInterface&MockObject $flushDataHandler;
    private HandlePayPalExpressPaymentSubresource $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->splitOrderActions = $this->createMock(SplitOrderActionsInterface::class);
        $this->checkoutActions = $this->createMock(CheckoutActionsInterface::class);
        $this->addressActions = $this->createMock(AddressActionsInterface::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->paymentStatusManager = $this->createMock(PaymentStatusManager::class);
        $this->groupedCheckoutLineItemsProvider = $this->createMock(GroupedCheckoutLineItemsProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->flushDataHandler = $this->createMock(FlushDataHandlerInterface::class);

        $this->processor = new HandlePayPalExpressPaymentSubresource(
            $this->splitOrderActions,
            $this->checkoutActions,
            $this->addressActions,
            $this->actionExecutor,
            $this->paymentStatusManager,
            $this->groupedCheckoutLineItemsProvider,
            $this->doctrineHelper,
            $this->flushDataHandler
        );
    }

    private function expectSaveChanges(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(Checkout::class)
            ->willReturn($em);
        $this->flushDataHandler->expects(self::once())
            ->method('flushData')
            ->with(self::identicalTo($em), self::isInstanceOf(FlushDataHandlerContext::class))
            ->willReturnCallback(function (EntityManagerInterface $entityManager, FlushDataHandlerContext $context) {
                /** @var ChangeSubresourceContext $entityContext */
                $entityContext = $context->getEntityContexts()[0];
                self::assertCount(0, $entityContext->getAdditionalEntityCollection()->getEntities());
            });
    }

    private function expectSaveChangesAndRemoveOrder(Order $order): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(Checkout::class)
            ->willReturn($em);
        $this->flushDataHandler->expects(self::once())
            ->method('flushData')
            ->with(self::identicalTo($em), self::isInstanceOf(FlushDataHandlerContext::class))
            ->willReturnCallback(function (
                EntityManagerInterface $entityManager,
                FlushDataHandlerContext $context
            ) use ($order) {
                /** @var ChangeSubresourceContext $entityContext */
                $entityContext = $context->getEntityContexts()[0];
                self::assertCount(1, $entityContext->getAdditionalEntityCollection()->getEntities());
                self::assertTrue($entityContext->getAdditionalEntityCollection()->shouldEntityBeRemoved($order));
            });
    }

    public function testProcessPaymentInProgressWithoutOrder(): void
    {
        $checkout = new Checkout();

        $checkout->setPaymentMethod('paypal_express');
        $checkout->setPaymentInProgress(true);

        $this->groupedCheckoutLineItemsProvider->expects(self::never())
            ->method('getGroupedLineItemsIds');

        $this->paymentStatusManager->expects(self::never())
            ->method('getPaymentStatus');

        $this->context->setParentEntity($checkout);
        $this->context->setAssociationName('test');
        $this->processor->process($this->context);

        self::assertTrue($checkout->isPaymentInProgress());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                Error::createValidationError(
                    'payment constraint',
                    'Can not process payment without order.'
                ),
            ],
            $this->context->getErrors()
        );
        self::assertFalse($this->context->isProcessed(SaveParentEntity::OPERATION_NAME));
    }

    public function testProcessPaymentInProgressWithNotFinishedStatus(): void
    {
        $checkout = new Checkout();
        $order = new Order();

        $checkout->setPaymentMethod('paypal_express');
        $checkout->setOrder($order);
        $checkout->setPaymentInProgress(true);

        $this->groupedCheckoutLineItemsProvider->expects(self::never())
            ->method('getGroupedLineItemsIds');

        $paymentStatus = new PaymentStatus();
        $paymentStatus->setPaymentStatus(PaymentStatuses::PENDING);

        $this->paymentStatusManager->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn($paymentStatus);

        $this->context->setParentEntity($checkout);
        $this->context->setAssociationName('test');
        $this->processor->process($this->context);

        self::assertTrue($checkout->isPaymentInProgress());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                Error::createValidationError(
                    'payment status constraint',
                    'Payment is being processed. Please follow the payment provider\'s instructions to complete.'
                ),
            ],
            $this->context->getErrors()
        );
        self::assertFalse($this->context->isProcessed(SaveParentEntity::OPERATION_NAME));
    }

    public function testProcessPaymentInProgressWithErrorStatus(): void
    {
        $checkout = new Checkout();
        $order = new Order();

        $checkout->setPaymentMethod('paypal_express');
        $checkout->setOrder($order);
        $checkout->setPaymentInProgress(true);

        $this->groupedCheckoutLineItemsProvider->expects(self::never())
            ->method('getGroupedLineItemsIds');

        $paymentStatus = new PaymentStatus();
        $paymentStatus->setPaymentStatus(PaymentStatuses::DECLINED);

        $this->paymentStatusManager->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn($paymentStatus);

        $this->expectSaveChangesAndRemoveOrder($order);

        $this->context->setParentEntity($checkout);
        $this->context->setAssociationName('test');
        $this->processor->process($this->context);

        self::assertFalse($checkout->isPaymentInProgress());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                Error::createValidationError(
                    'payment constraint',
                    'Payment failed, please try again or select a different payment method.'
                ),
            ],
            $this->context->getErrors()
        );
        self::assertTrue($this->context->isProcessed(SaveParentEntity::OPERATION_NAME));
    }

    public function testProcessPaymentInProgress(): void
    {
        $checkout = new Checkout();
        $order = new Order();

        $checkout->setPaymentMethod('paypal_express');
        $checkout->setOrder($order);
        $checkout->setPaymentInProgress(true);

        $this->groupedCheckoutLineItemsProvider->expects(self::never())
            ->method('getGroupedLineItemsIds');

        $paymentStatus = new PaymentStatus();
        $paymentStatus->setPaymentStatus(PaymentStatuses::PAID_IN_FULL);

        $this->paymentStatusManager->expects(self::once())
            ->method('getPaymentStatus')
            ->with($order)
            ->willReturn($paymentStatus);

        $this->addressActions->expects(self::once())
            ->method('actualizeAddresses')
            ->with($checkout, $order);
        $this->checkoutActions->expects(self::once())
            ->method('fillCheckoutCompletedData')
            ->with($checkout, $order);

        $this->expectSaveChanges();

        $this->context->setParentEntity($checkout);
        $this->context->setAssociationName('test');
        $this->context->setResult($order);
        $this->processor->process($this->context);

        self::assertFalse($checkout->isPaymentInProgress());
        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(SaveParentEntity::OPERATION_NAME));
    }

    public function testProcessExecutePurchaseWithNotRedirectError(): void
    {
        $checkout = new Checkout();
        $checkout->setPaymentMethod('paypal_express');
        $order = new Order();
        $order->setTotal(100.0);
        $order->setCurrency('USD');
        $checkout->setOrder($order);
        $groupedLineItemIds = ['group1' => ['item1']];

        $request = new PayPalExpressPaymentRequest();
        $request->setFailureUrl('failureUrl');
        $request->setSuccessUrl('successUrl');

        $this->groupedCheckoutLineItemsProvider->expects(self::once())
            ->method('getGroupedLineItemsIds')
            ->with($checkout)
            ->willReturn($groupedLineItemIds);
        $this->splitOrderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout, $groupedLineItemIds)
            ->willReturn($order);
        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                'payment_purchase',
                [
                    'attribute' => new PropertyPath('response'),
                    'object' => $order,
                    'amount' => 100.0,
                    'currency' => 'USD',
                    'paymentMethod' => 'paypal_express',
                    'transactionOptions' => [
                        'failureUrl' => 'failureUrl',
                        'successUrl' => 'successUrl',
                    ],
                ]
            )
            ->willReturn([
                'response' => [
                    'successful' => false,
                    'purchaseRedirectUrl' => null,
                ],
            ]);

        $this->expectSaveChangesAndRemoveOrder($order);

        $this->context->setParentEntity($checkout);
        $this->context->setAssociationName('test');
        $this->context->setResult(['test' => $request]);
        $this->processor->process($this->context);

        self::assertFalse($checkout->isPaymentInProgress());
        self::assertNull($checkout->getOrder());
        self::assertTrue($this->context->hasErrors());
        self::assertEquals(
            [
                Error::createValidationError(
                    'payment constraint',
                    'Payment failed, please try again or select a different payment method.'
                ),
            ],
            $this->context->getErrors()
        );
        self::assertTrue($this->context->isProcessed(SaveParentEntity::OPERATION_NAME));
    }

    public function testProcessExecutePurchaseWithRedirect(): void
    {
        $checkout = new Checkout();
        $checkout->setPaymentMethod('paypal_express');
        $order = new Order();
        $order->setTotal(100.0);
        $order->setCurrency('USD');
        $checkout->setOrder($order);
        $groupedLineItemIds = ['group1' => ['item1']];

        $request = new PayPalExpressPaymentRequest();
        $request->setFailureUrl('failureUrl');
        $request->setSuccessUrl('successUrl');

        $this->groupedCheckoutLineItemsProvider->expects(self::once())
            ->method('getGroupedLineItemsIds')
            ->with($checkout)
            ->willReturn($groupedLineItemIds);
        $this->splitOrderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout, $groupedLineItemIds)
            ->willReturn($order);
        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                'payment_purchase',
                [
                    'attribute' => new PropertyPath('response'),
                    'object' => $order,
                    'amount' => 100.0,
                    'currency' => 'USD',
                    'paymentMethod' => 'paypal_express',
                    'transactionOptions' => [
                        'failureUrl' => 'failureUrl',
                        'successUrl' => 'successUrl',
                    ],
                ]
            )
            ->willReturn([
                'response' => [
                    'successful' => false,
                    'purchaseRedirectUrl' => 'redirectUrl',
                ],
            ]);

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManagerForClass');

        $this->context->setParentEntity($checkout);
        $this->context->setAssociationName('test');
        $this->context->setResult(['test' => $request]);
        $this->processor->process($this->context);

        self::assertTrue($checkout->isPaymentInProgress());
        self::assertEquals($order, $checkout->getOrder());
        self::assertTrue($this->context->hasErrors());
        $error = Error::createValidationError(
            'payment action constraint',
            'Payment should be completed on the merchant\'s page, follow the link provided in the error details.'
        );
        $error->addMetaProperty(
            'data',
            new ErrorMetaProperty(['successful' => false, 'purchaseRedirectUrl' => 'redirectUrl'], 'array')
        );
        self::assertEquals([$error], $this->context->getErrors());
        self::assertFalse($this->context->isProcessed(SaveParentEntity::OPERATION_NAME));
    }

    public function testProcessExecutePurchase(): void
    {
        $checkout = new Checkout();
        $checkout->setPaymentMethod('paypal_express');
        $order = new Order();
        $order->setTotal(100.0);
        $order->setCurrency('USD');
        $checkout->setOrder($order);
        $groupedLineItemIds = ['group1' => ['item1']];

        $request = new PayPalExpressPaymentRequest();
        $request->setFailureUrl('failureUrl');
        $request->setSuccessUrl('successUrl');

        $this->groupedCheckoutLineItemsProvider->expects(self::once())
            ->method('getGroupedLineItemsIds')
            ->with($checkout)
            ->willReturn($groupedLineItemIds);
        $this->splitOrderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout, $groupedLineItemIds)
            ->willReturn($order);
        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                'payment_purchase',
                [
                    'attribute' => new PropertyPath('response'),
                    'object' => $order,
                    'amount' => 100.0,
                    'currency' => 'USD',
                    'paymentMethod' => 'paypal_express',
                    'transactionOptions' => [
                        'failureUrl' => 'failureUrl',
                        'successUrl' => 'successUrl',
                    ],
                ]
            )
            ->willReturn(['response' => ['successful' => true]]);

        $this->addressActions->expects(self::once())
            ->method('actualizeAddresses')
            ->with($checkout, $order);
        $this->checkoutActions->expects(self::once())
            ->method('fillCheckoutCompletedData')
            ->with($checkout, $order);

        $this->expectSaveChanges();

        $this->context->setParentEntity($checkout);
        $this->context->setAssociationName('test');
        $this->context->setResult(['test' => $request]);
        $this->processor->process($this->context);

        self::assertFalse($checkout->isPaymentInProgress());
        self::assertEquals($order, $checkout->getOrder());
        self::assertFalse($this->context->hasErrors());
        self::assertTrue($this->context->isProcessed(SaveParentEntity::OPERATION_NAME));
    }
}
