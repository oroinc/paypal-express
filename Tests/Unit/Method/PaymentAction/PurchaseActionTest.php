<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfig;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CompleteVirtualAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PurchaseAction;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalTransportFacadeInterface;

class PurchaseActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PayPalTransportFacadeInterface
     */
    protected $facade;

    /**
     * @var PurchaseAction
     */
    protected $action;

    protected function setUp()
    {
        $this->facade = $this->createMock(PayPalTransportFacadeInterface::class);

        $this->action = new PurchaseAction($this->facade);
    }

    public function testExecuteAction()
    {
        $expectedUrl = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=EC-60385559L1062554J';

        $transaction = new PaymentTransaction();
        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            true
        );

        $this->facade->expects($this->once())
            ->method('getPayPalPaymentRoute')
            ->with($transaction, $config)
            ->willReturn($expectedUrl);

        $result = $this->action->executeAction($transaction, $config);

        $this->assertEquals(PurchaseAction::PAYMENT_TRANSACTION_ACTION_NAME, $transaction->getAction());
        $this->assertTrue($transaction->isActive());
        $this->assertTrue($transaction->isSuccessful());

        $this->assertEquals(['purchaseRedirectUrl' => $expectedUrl], $result);
    }

    public function testExecuteActionShouldRecoverAfterPayPalInnerException()
    {
        $transaction = new PaymentTransaction();
        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            true
        );

        $expectedMessage = 'Order Id is required';
        $this->facade->expects($this->once())
            ->method('getPayPalPaymentRoute')
            ->willThrowException(new RuntimeException($expectedMessage));

        $result = $this->action->executeAction($transaction, $config);

        $this->assertEquals(PurchaseAction::PAYMENT_TRANSACTION_ACTION_NAME, $transaction->getAction());
        $this->assertFalse($transaction->isActive());
        $this->assertFalse($transaction->isSuccessful());

        $this->assertEquals([], $result);
    }

    public function testExecuteActionShouldNotRecoverAfterUnrecoverableException()
    {
        $transaction = new PaymentTransaction();
        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            true
        );

        $expectedMessage = 'Order Id is required';
        $this->facade->expects($this->once())
            ->method('getPayPalPaymentRoute')
            ->willThrowException(new \RuntimeException($expectedMessage));


        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->action->executeAction($transaction, $config);
    }
}
