<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction\Complete;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfig;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeAndCaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CompleteVirtualAction;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalTransportFacadeInterface;

class AuthorizeAndCaptureActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PayPalTransportFacadeInterface
     */
    protected $facade;

    /**
     * @var AuthorizeAndCaptureAction
     */
    protected $action;

    protected function setUp()
    {
        $this->facade = $this->createMock(PayPalTransportFacadeInterface::class);

        $this->action = new AuthorizeAndCaptureAction($this->facade);
    }

    public function testExecuteAction()
    {
        $transaction = new PaymentTransaction();

        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            CompleteVirtualAction::NAME,
            AuthorizeAndCaptureAction::NAME,
            true
        );

        $this->facade->expects($this->at(0))
            ->method('executePayPalPayment')
            ->with($transaction, $config);

        $this->facade->expects($this->at(1))
            ->method('authorizePayment')
            ->with($transaction, $config);

        $this->facade->expects($this->at(2))
            ->method('capturePayment')
            ->with($transaction, $transaction, $config);

        $result = $this->action->executeAction($transaction, $config);

        $this->assertEquals($this->action->getName(), $transaction->getAction());
        $this->assertFalse($transaction->isActive());
        $this->assertTrue($transaction->isSuccessful());

        $this->assertEquals(['successful' => true], $result);
    }
}
