<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PurchaseAction;

class PurchaseActionTest extends AbstractPaymentActionTestCase
{
    /**
     * @return PaymentActionInterface
     */
    protected function createPaymentAction()
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

    protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable)
    {
        $this->facade->expects($this->once())
            ->method('getPayPalPaymentRoute')
            ->will($this->throwException($throwable));
    }

    /**
     * @return string
     */
    protected function getExpectedPaymentTransactionAction()
    {
        return PurchaseAction::PAYMENT_TRANSACTION_ACTION_NAME;
    }

    /**
     * @return array
     */
    protected function getExpectedExecuteResultAfterPayPalInnerException(ExceptionInterface $exception)
    {
        return [];
    }
}
