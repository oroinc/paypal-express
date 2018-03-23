<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfig;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalTransportFacadeInterface;

class CaptureActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PayPalTransportFacadeInterface
     */
    protected $facade;

    /**
     * @var CaptureAction
     */
    protected $action;

    protected function setUp()
    {
        $this->facade = $this->createMock(PayPalTransportFacadeInterface::class);

        $this->action = new CaptureAction($this->facade);
    }

    public function testExecuteAction()
    {
        $transaction = new PaymentTransaction();
        $sourceTransaction = new PaymentTransaction();
        $transaction->setSourcePaymentTransaction($sourceTransaction);

        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            '',
            PaymentMethodInterface::CAPTURE,
            true
        );

        $this->facade->expects($this->once())
            ->method('capturePayment')
            ->with($transaction, $sourceTransaction, $config);

        $result = $this->action->executeAction($transaction, $config);

        $this->assertEquals(PaymentMethodInterface::CAPTURE, $transaction->getAction());
        $this->assertFalse($transaction->isActive());
        $this->assertTrue($transaction->isSuccessful());

        $this->assertEquals(['successful' => true], $result);
    }

    public function testExecuteActionShouldReturnAnErrorIfSourceTransactionDoesNotSet()
    {
        $transaction = new PaymentTransaction();

        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            '',
            PaymentMethodInterface::CAPTURE,
            true
        );

        $this->facade->expects($this->never())
            ->method('capturePayment');

        $result = $this->action->executeAction($transaction, $config);

        $this->assertEquals(PaymentMethodInterface::CAPTURE, $transaction->getAction());
        $this->assertFalse($transaction->isActive());
        $this->assertFalse($transaction->isSuccessful());

        $this->assertEquals(
            [
                'successful' => false,
                'message' => 'Could not capture payment, transaction with approved payment not found'
            ],
            $result
        );
    }

    public function testExecuteActionShouldRecoverAfterPayPalInnerException()
    {
        $transaction = new PaymentTransaction();
        $sourceTransaction = new PaymentTransaction();
        $transaction->setSourcePaymentTransaction($sourceTransaction);

        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            '',
            PaymentMethodInterface::CAPTURE,
            true
        );

        $expectedMessage = 'Order Id is required';

        $this->facade->expects($this->any())
            ->method('capturePayment')
            ->willThrowException(new RuntimeException($expectedMessage));

        $result = $this->action->executeAction($transaction, $config);

        $this->assertEquals(PaymentMethodInterface::CAPTURE, $transaction->getAction());
        $this->assertFalse($transaction->isActive());
        $this->assertFalse($transaction->isSuccessful());

        $this->assertEquals(['successful' => false, 'message' => $expectedMessage], $result);
    }

    public function testExecuteActionShouldNotRecoverAfterUnrecoverableException()
    {
        $transaction = new PaymentTransaction();
        $sourceTransaction = new PaymentTransaction();
        $transaction->setSourcePaymentTransaction($sourceTransaction);

        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            '',
            PaymentMethodInterface::CAPTURE,
            true
        );

        $expectedMessage = 'Order Id is required';

        $this->facade->expects($this->any())
            ->method('capturePayment')
            ->willThrowException(new \RuntimeException($expectedMessage));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->action->executeAction($transaction, $config);
    }
}
