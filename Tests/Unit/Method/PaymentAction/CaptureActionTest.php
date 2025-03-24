<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;

class CaptureActionTest extends AbstractPaymentActionTestCase
{
    #[\Override]
    protected function createPaymentAction(): PaymentActionInterface
    {
        return new CaptureAction($this->facade, $this->logger);
    }

    #[\Override]
    protected function createPaymentTransaction(): PaymentTransaction
    {
        $transaction = new PaymentTransaction();
        $transaction->setSourcePaymentTransaction(new PaymentTransaction());

        return $transaction;
    }

    public function testExecuteAction(): void
    {
        $this->facade->expects(self::once())
            ->method('capturePayment')
            ->with($this->paymentTransaction, $this->paymentTransaction->getSourcePaymentTransaction(), $this->config);

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        self::assertEquals(PaymentMethodInterface::CAPTURE, $this->paymentTransaction->getAction());
        self::assertFalse($this->paymentTransaction->isActive());
        self::assertTrue($this->paymentTransaction->isSuccessful());

        self::assertEquals(['successful' => true], $result);
    }

    public function testExecuteActionShouldReturnAnErrorIfSourceTransactionDoesNotSet(): void
    {
        $this->paymentTransaction = new PaymentTransaction();

        $this->facade->expects(self::never())
            ->method('capturePayment');

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        self::assertEquals(PaymentMethodInterface::CAPTURE, $this->paymentTransaction->getAction());
        self::assertFalse($this->paymentTransaction->isActive());
        self::assertFalse($this->paymentTransaction->isSuccessful());

        self::assertEquals(
            [
                'successful' => false,
                'message' => 'oro.paypal_express.error_message.capture_action.source_payment_transaction_not_found'
            ],
            $result
        );
    }

    #[\Override]
    protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable): void
    {
        $this->facade->expects(self::any())
            ->method('capturePayment')
            ->willThrowException($throwable);
    }

    #[\Override]
    protected function getExpectedPaymentTransactionAction(): string
    {
        return PaymentMethodInterface::CAPTURE;
    }

    #[\Override]
    protected function getExpectedExecuteResultAfterPayPalInnerException(ExceptionInterface $exception): array
    {
        return ['successful' => false, 'message' => $exception->getMessage()];
    }
}
