<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PurchaseAction;

class PurchaseActionTest extends AbstractPaymentActionTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createPaymentAction(): PaymentActionInterface
    {
        return new PurchaseAction($this->facade, $this->logger);
    }

    public function testExecuteAction()
    {
        $expectedUrl = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-60385559L1062554J';

        $this->facade->expects($this->once())
            ->method('getPayPalPaymentRoute')
            ->with($this->paymentTransaction, $this->config)
            ->willReturn($expectedUrl);

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        $this->assertEquals(PurchaseAction::PAYMENT_TRANSACTION_ACTION_NAME, $this->paymentTransaction->getAction());
        $this->assertTrue($this->paymentTransaction->isActive());
        $this->assertTrue($this->paymentTransaction->isSuccessful());

        $this->assertEquals(['purchaseRedirectUrl' => $expectedUrl], $result);
    }

    /**
     * {@inheritDoc}
     */
    protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable): void
    {
        $this->facade->expects($this->once())
            ->method('getPayPalPaymentRoute')
            ->willThrowException($throwable);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedPaymentTransactionAction(): string
    {
        return PurchaseAction::PAYMENT_TRANSACTION_ACTION_NAME;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedExecuteResultAfterPayPalInnerException(ExceptionInterface $exception): array
    {
        return [];
    }
}
