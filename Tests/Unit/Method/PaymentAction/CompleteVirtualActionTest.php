<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfig;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\AuthorizeAndCaptureAction;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete\CompletePaymentActionRegistry;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\CompleteVirtualAction;

class CompleteVirtualActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CompleteVirtualAction
     */
    protected $action;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CompletePaymentActionRegistry
     */
    protected $completePaymentActionRegistry;

    protected function setUp()
    {
        $this->completePaymentActionRegistry = $this->createMock(CompletePaymentActionRegistry::class);

        $this->action = new CompleteVirtualAction($this->completePaymentActionRegistry);
    }

    public function testExecuteAction()
    {
        $completePaymentAction = AuthorizeAndCaptureAction::NAME;

        $transaction = new PaymentTransaction();

        $config = new PayPalExpressConfig(
            '',
            '',
            '',
            '',
            '',
            CompleteVirtualAction::NAME,
            $completePaymentAction,
            true
        );

        $actualAction = $this->createMock(AuthorizeAndCaptureAction::class);

        $this->completePaymentActionRegistry
            ->expects($this->once())
            ->method('getPaymentAction')
            ->with($completePaymentAction)
            ->willReturn($actualAction);

        $actualAction->expects($this->once())
            ->method('executeAction')
            ->with($transaction, $config);

        $this->action->executeAction($transaction, $config);
    }
}
