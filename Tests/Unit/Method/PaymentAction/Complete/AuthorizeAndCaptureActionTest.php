<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction\Complete;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeAndCaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction\AbstractPaymentActionTestCase;

class AuthorizeAndCaptureActionTest extends AbstractPaymentActionTestCase
{
    /**
     * @return PaymentActionInterface
     */
    protected function createPaymentAction()
    {
        return new AuthorizeAndCaptureAction($this->facade, $this->logger);
    }

    /**
     * @return string
     */
    protected function getExpectedPaymentTransactionAction()
    {
        return PaymentMethodInterface::CAPTURE;
    }

    protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable)
    {
        $this->facade->expects($this->any())
            ->method('executePayPalPayment')
            ->will($this->throwException($throwable));
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
