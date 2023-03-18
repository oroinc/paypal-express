<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction\Complete;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeAndCaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction\AbstractPaymentActionTestCase;

class AuthorizeAndCaptureActionTest extends AbstractPaymentActionTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createPaymentAction(): PaymentActionInterface
    {
        return new AuthorizeAndCaptureAction($this->facade, $this->logger);
    }

    /**
     * {@inheritDoc}
     */
    protected function getExpectedPaymentTransactionAction(): string
    {
        return PaymentMethodInterface::CAPTURE;
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

    public function testExecuteAction()
    {
        $this->facade->expects($this->once())
            ->method('executePayPalPayment')
            ->with($this->paymentTransaction, $this->config);

        $this->facade->expects($this->once())
            ->method('capturePayment')
            ->with($this->paymentTransaction, $this->paymentTransaction, $this->config);

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        $this->assertEquals(PaymentMethodInterface::CAPTURE, $this->paymentTransaction->getAction());
        $this->assertFalse($this->paymentTransaction->isActive());
        $this->assertTrue($this->paymentTransaction->isSuccessful());

        $this->assertEquals(['successful' => true], $result);
    }
}
