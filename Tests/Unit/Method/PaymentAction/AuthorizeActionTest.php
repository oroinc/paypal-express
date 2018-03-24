<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Exception;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\AuthorizeAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;

class AuthorizeActionTest extends AbstractPaymentActionTestCase
{
    /**
     * @return PaymentActionInterface
     */
    protected function createPaymentAction()
    {
        return new AuthorizeAction($this->facade, $this->logger);
    }

    public function testExecuteAction()
    {
        $this->facade->expects($this->at(0))
            ->method('executePayPalPayment')
            ->with($this->paymentTransaction, $this->config);

        $this->facade->expects($this->at(1))
            ->method('authorizePayment')
            ->with($this->paymentTransaction, $this->config);

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        $this->assertEquals(PaymentMethodInterface::AUTHORIZE, $this->paymentTransaction->getAction());
        $this->assertTrue($this->paymentTransaction->isActive());
        $this->assertTrue($this->paymentTransaction->isSuccessful());

        $this->assertEquals(['successful' => true], $result);
    }

    /**
     * @return string
     */
    protected function getExpectedPaymentTransactionAction()
    {
        return PaymentMethodInterface::AUTHORIZE;
    }

    /**
     * @return array
     */
    protected function getExpectedExecuteResultAfterPayPalInnerException(Exception\ExceptionInterface $exception)
    {
        return ['successful' => false, 'message' => $exception->getMessage()];
    }

    /**
     * @param \Throwable $throwable
     */
    protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable)
    {
        $this->facade->expects($this->any())
            ->method('executePayPalPayment')
            ->will($this->throwException($throwable));
    }
}
