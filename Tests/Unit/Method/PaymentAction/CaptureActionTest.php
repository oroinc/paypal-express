<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;

class CaptureActionTest extends AbstractPaymentActionTestCase
{
    /**
     * @return PaymentActionInterface
     */
    protected function createPaymentAction()
    {
        return new CaptureAction($this->facade, $this->logger);
    }

    protected function createPaymentTransaction()
    {
        $transaction = new PaymentTransaction();
        $sourceTransaction = new PaymentTransaction();
        $transaction->setSourcePaymentTransaction($sourceTransaction);
        return $transaction;
    }

    public function testExecuteAction()
    {
        $this->facade->expects($this->once())
            ->method('capturePayment')
            ->with($this->paymentTransaction, $this->paymentTransaction->getSourcePaymentTransaction(), $this->config);

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        $this->assertEquals(PaymentMethodInterface::CAPTURE, $this->paymentTransaction->getAction());
        $this->assertFalse($this->paymentTransaction->isActive());
        $this->assertTrue($this->paymentTransaction->isSuccessful());

        $this->assertEquals(['successful' => true], $result);
    }

    public function testExecuteActionShouldReturnAnErrorIfSourceTransactionDoesNotSet()
    {
        $this->paymentTransaction = new PaymentTransaction();

        $this->facade->expects($this->never())
            ->method('capturePayment');

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        $this->assertEquals(PaymentMethodInterface::CAPTURE, $this->paymentTransaction->getAction());
        $this->assertFalse($this->paymentTransaction->isActive());
        $this->assertFalse($this->paymentTransaction->isSuccessful());

        $this->assertEquals(
            [
                'successful' => false,
                'message' => 'oro.paypal_express.error_message.capture_action.source_payment_transaction_not_found'
            ],
            $result
        );
    }

    protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable)
    {
        $this->facade->expects($this->any())
            ->method('capturePayment')
            ->will($this->throwException($throwable));
    }

    /**
     * @return string
     */
    protected function getExpectedPaymentTransactionAction()
    {
        return PaymentMethodInterface::CAPTURE;
    }

    /**
     * @return array
     */
    protected function getExpectedExecuteResultAfterPayPalInnerException(ExceptionInterface $exception)
    {
        return ['successful' => false, 'message' => $exception->getMessage()];
    }
}
