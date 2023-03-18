<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\AuthorizeAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;

class AuthorizeActionTest extends AbstractPaymentActionTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createPaymentAction(): PaymentActionInterface
    {
        return new AuthorizeAction($this->facade, $this->logger);
    }

    public function testExecuteAction()
    {
        $this->facade->expects($this->once())
            ->method('executePayPalPayment')
            ->with($this->paymentTransaction, $this->config);
        $this->facade->expects($this->once())
            ->method('authorizePayment')
            ->with($this->paymentTransaction, $this->config);

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        $this->assertEquals(PaymentMethodInterface::AUTHORIZE, $this->paymentTransaction->getAction());
        $this->assertTrue($this->paymentTransaction->isActive());
        $this->assertTrue($this->paymentTransaction->isSuccessful());

        $this->assertEquals(['successful' => true], $result);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedPaymentTransactionAction(): string
    {
        return PaymentMethodInterface::AUTHORIZE;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedExecuteResultAfterPayPalInnerException(ExceptionInterface $exception): array
    {
        return ['successful' => false, 'message' => $exception->getMessage()];
    }

    /**
     * {@inheritDoc}
     */
    protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable): void
    {
        $this->facade->expects($this->any())
            ->method('executePayPalPayment')
            ->willThrowException($throwable);
    }
}
