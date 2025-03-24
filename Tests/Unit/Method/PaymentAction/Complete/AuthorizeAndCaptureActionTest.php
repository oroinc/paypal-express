<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction\Complete;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeAndCaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction\AbstractPaymentActionTestCase;

class AuthorizeAndCaptureActionTest extends AbstractPaymentActionTestCase
{
    #[\Override]
    protected function createPaymentAction(): PaymentActionInterface
    {
        return new AuthorizeAndCaptureAction($this->facade, $this->logger);
    }

    #[\Override]
    protected function getExpectedPaymentTransactionAction(): string
    {
        return PaymentMethodInterface::CAPTURE;
    }

    #[\Override]
    protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable): void
    {
        $this->facade->expects(self::any())
            ->method('executePayPalPayment')
            ->willThrowException($throwable);
    }

    public function testExecuteAction(): void
    {
        $this->facade->expects(self::once())
            ->method('executePayPalPayment')
            ->with($this->paymentTransaction, $this->config);

        $this->facade->expects(self::once())
            ->method('capturePayment')
            ->with($this->paymentTransaction, $this->paymentTransaction, $this->config);

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        self::assertEquals(PaymentMethodInterface::CAPTURE, $this->paymentTransaction->getAction());
        self::assertFalse($this->paymentTransaction->isActive());
        self::assertTrue($this->paymentTransaction->isSuccessful());

        self::assertEquals(['successful' => true], $result);
    }
}
