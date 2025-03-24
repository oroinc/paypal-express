<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\AuthorizeAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;

class AuthorizeActionTest extends AbstractPaymentActionTestCase
{
    #[\Override]
    protected function createPaymentAction(): PaymentActionInterface
    {
        return new AuthorizeAction($this->facade, $this->logger);
    }

    public function testExecuteAction(): void
    {
        $this->facade->expects(self::once())
            ->method('executePayPalPayment')
            ->with($this->paymentTransaction, $this->config);
        $this->facade->expects(self::once())
            ->method('authorizePayment')
            ->with($this->paymentTransaction, $this->config);

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        self::assertEquals(PaymentMethodInterface::AUTHORIZE, $this->paymentTransaction->getAction());
        self::assertTrue($this->paymentTransaction->isActive());
        self::assertTrue($this->paymentTransaction->isSuccessful());

        self::assertEquals(['successful' => true], $result);
    }

    #[\Override]
    protected function getExpectedPaymentTransactionAction(): string
    {
        return PaymentMethodInterface::AUTHORIZE;
    }

    #[\Override]
    protected function getExpectedExecuteResultAfterPayPalInnerException(ExceptionInterface $exception): array
    {
        return ['successful' => false, 'message' => $exception->getMessage()];
    }

    #[\Override]
    protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable): void
    {
        $this->facade->expects(self::any())
            ->method('executePayPalPayment')
            ->willThrowException($throwable);
    }
}
