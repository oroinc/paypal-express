<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PurchaseAction;

class PurchaseActionTest extends AbstractPaymentActionTestCase
{
    #[\Override]
    protected function createPaymentAction(): PaymentActionInterface
    {
        return new PurchaseAction($this->facade, $this->logger);
    }

    public function testExecuteAction(): void
    {
        $expectedUrl = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-60385559L1062554J';

        $this->facade->expects(self::once())
            ->method('getPayPalPaymentRoute')
            ->with($this->paymentTransaction, $this->config)
            ->willReturn($expectedUrl);

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        self::assertEquals(PurchaseAction::PAYMENT_TRANSACTION_ACTION_NAME, $this->paymentTransaction->getAction());
        self::assertTrue($this->paymentTransaction->isActive());
        self::assertTrue($this->paymentTransaction->isSuccessful());

        self::assertEquals(['purchaseRedirectUrl' => $expectedUrl], $result);
    }

    #[\Override]
    protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable): void
    {
        $this->facade->expects(self::once())
            ->method('getPayPalPaymentRoute')
            ->willThrowException($throwable);
    }

    #[\Override]
    protected function getExpectedPaymentTransactionAction(): string
    {
        return PurchaseAction::PAYMENT_TRANSACTION_ACTION_NAME;
    }

    #[\Override]
    protected function getExpectedExecuteResultAfterPayPalInnerException(ExceptionInterface $exception): array
    {
        return [];
    }
}
