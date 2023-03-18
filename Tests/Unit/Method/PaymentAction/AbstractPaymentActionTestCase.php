<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PurchaseAction;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalExpressTransportFacadeInterface;
use PHPUnit\Framework\Constraint\Constraint;
use Psr\Log\LoggerInterface;

abstract class AbstractPaymentActionTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var PayPalExpressTransportFacadeInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $facade;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var PurchaseAction */
    protected $action;

    /** @var PaymentTransaction */
    protected $paymentTransaction;

    /** @var PayPalExpressConfigInterface */
    protected $config;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(PayPalExpressTransportFacadeInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->action = $this->createPaymentAction();

        $this->paymentTransaction = $this->createPaymentTransaction();
        $this->config = $this->createMock(PayPalExpressConfigInterface::class);
    }

    abstract protected function createPaymentAction(): PaymentActionInterface;

    protected function createPaymentTransaction(): PaymentTransaction
    {
        return new PaymentTransaction();
    }

    public function testExecuteActionShouldRecoverAfterPayPalInnerException()
    {
        $expectedException = $this->createPayPalInnerException();

        $this->expectFacadeWillThrowErrorOnExecute($expectedException);
        $this->expectPaymentActionErrorLogged($this->action, $this->paymentTransaction, $expectedException);

        $result = $this->action->executeAction($this->paymentTransaction, $this->config);

        $this->assertEquals($this->getExpectedPaymentTransactionAction(), $this->paymentTransaction->getAction());
        $this->assertFalse($this->paymentTransaction->isActive());
        $this->assertFalse($this->paymentTransaction->isSuccessful());

        $this->assertEquals($this->getExpectedExecuteResultAfterPayPalInnerException($expectedException), $result);
    }

    abstract protected function getExpectedPaymentTransactionAction(): string;

    protected function getExpectedExecuteResultAfterPayPalInnerException(ExceptionInterface $exception): array
    {
        return ['successful' => false, 'message' => $exception->getMessage()];
    }

    protected function createPayPalInnerException(string $message = 'Order Id is required'): ExceptionInterface
    {
        return new RuntimeException($message);
    }

    abstract protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable): void;

    public function testExecuteActionShouldNotRecoverAfterUnrecoverableException()
    {
        $expectedException = $this->createUnrecoverableException();

        $this->expectFacadeWillThrowErrorOnExecute($expectedException);
        $this->expectPaymentActionErrorLogged($this->action, $this->paymentTransaction, $expectedException);

        $this->expectException(get_class($expectedException));
        $this->expectExceptionMessage($expectedException->getMessage());

        $this->action->executeAction($this->paymentTransaction, $this->config);
    }

    protected function createUnrecoverableException(string $message = 'Internal server error'): \RuntimeException
    {
        return new \RuntimeException($message);
    }

    public function testExecuteActionShouldNotRecoverAfterUnrecoverableError()
    {
        $expectedError = $this->createUnrecoverableError();

        $this->expectFacadeWillThrowErrorOnExecute($expectedError);
        $this->expectPaymentActionErrorLogged($this->action, $this->paymentTransaction, $expectedError);

        $this->expectException(get_class($expectedError));
        $this->expectExceptionMessage($expectedError->getMessage());

        $this->action->executeAction($this->paymentTransaction, $this->config);
    }

    protected function createUnrecoverableError(string $message = 'Fatal error'): \Error
    {
        return new \Error($message);
    }

    protected function expectPaymentActionErrorLogged(
        PaymentActionInterface $paymentAction,
        PaymentTransaction $paymentTransaction,
        \Throwable $expectedException
    ): void {
        $expectedAction = $paymentAction->getName();
        $expectedMethod = $paymentTransaction->getPaymentMethod();
        $expectedTransactionId = $paymentTransaction->getId();
        $expectedReason = $expectedException->getMessage();

        $expectedErrorMessage = sprintf('Payment %s failed. Reason: %s', $expectedAction, $expectedReason);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $expectedErrorMessage,
                $this->logicalAnd(
                    $this->arrayKeyMatches('exception', $expectedException),
                    $this->arrayKeyMatches('payment_transaction_id', $expectedTransactionId),
                    $this->arrayKeyMatches('payment_method', $expectedMethod)
                )
            );
    }

    protected function arrayKeyMatches(string $expectedKey, mixed $constraint): Constraint
    {
        if (!$constraint instanceof Constraint) {
            $constraint = $this->equalTo($constraint);
        }

        return $this->callback(
            function ($array) use ($expectedKey, $constraint) {
                $this->assertIsArray($array, 'Failed asserting value is array.');
                $this->assertArrayHasKey($expectedKey, $array);
                $constraint->evaluate(
                    $array[$expectedKey],
                    sprintf('Failed asserting that array key "%s" matches expected value.', $expectedKey)
                );

                return true;
            }
        );
    }
}
