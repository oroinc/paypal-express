<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PayPalExpressBundle\Exception;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PaymentActionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\PurchaseAction;
use Oro\Bundle\PayPalExpressBundle\Method\PayPalExpressTransportFacadeInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractPaymentActionTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PayPalExpressTransportFacadeInterface
     */
    protected $facade;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var PurchaseAction
     */
    protected $action;

    /**
     * @var PaymentTransaction
     */
    protected $paymentTransaction;

    /**
     * @var PayPalExpressConfigInterface
     */
    protected $config;

    protected function setUp(): void
    {
        $this->facade = $this->createMock(PayPalExpressTransportFacadeInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->action = $this->createPaymentAction();

        $this->paymentTransaction = $this->createPaymentTransaction();
        $this->config = $this->createConfig();
    }

    /**
     * @return PaymentActionInterface
     */
    abstract protected function createPaymentAction();

    /**
     * @return PaymentTransaction
     */
    protected function createPaymentTransaction()
    {
        return new PaymentTransaction();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|PayPalExpressConfigInterface
     */
    protected function createConfig()
    {
        return $this->createMock(PayPalExpressConfigInterface::class);
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

    /**
     * @return string
     */
    abstract protected function getExpectedPaymentTransactionAction();

    /**
     * @param Exception\ExceptionInterface $exception
     * @return array
     */
    protected function getExpectedExecuteResultAfterPayPalInnerException(Exception\ExceptionInterface $exception)
    {
        return ['successful' => false, 'message' => $exception->getMessage()];
    }
    /**
     * @param string $message
     * @return Exception\ExceptionInterface
     */
    protected function createPayPalInnerException($message = 'Order Id is required')
    {
        return new Exception\RuntimeException($message);
    }

    abstract protected function expectFacadeWillThrowErrorOnExecute(\Throwable $throwable);

    public function testExecuteActionShouldNotRecoverAfterUnrecoverableException()
    {
        $expectedException = $this->createUnrecoverableException();

        $this->expectFacadeWillThrowErrorOnExecute($expectedException);
        $this->expectPaymentActionErrorLogged($this->action, $this->paymentTransaction, $expectedException);

        $this->expectException(get_class($expectedException));
        $this->expectExceptionMessage($expectedException->getMessage());

        $this->action->executeAction($this->paymentTransaction, $this->config);
    }

    /**
     * @param string $message
     * @return \RuntimeException
     */
    protected function createUnrecoverableException($message = 'Internal server error')
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

    /**
     * @param string $message
     * @return \Error
     */
    protected function createUnrecoverableError($message = 'Fatal error')
    {
        return new \Error($message);
    }

    protected function expectPaymentActionErrorLogged(
        PaymentActionInterface $paymentAction,
        PaymentTransaction $paymentTransaction,
        \Throwable $expectedException
    ) {
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

    /**
     * @param string                              $expectedKey
     * @param mixed|\PHPUnit\Framework\Constraint\Constraint $constraint
     * @return \PHPUnit\Framework\Constraint\Constraint
     */
    protected function arrayKeyMatches($expectedKey, $constraint)
    {
        if (!$constraint instanceof \PHPUnit\Framework\Constraint\Constraint) {
            $constraint = $this->equalTo($constraint);
        }
        return $this->callback(
            function ($array) use ($expectedKey, $constraint) {
                $this->assertIsArray($array, 'Failed asserting value is array.');
                $this->assertArrayHasKey(
                    $expectedKey,
                    $array
                );
                $constraint->evaluate(
                    $array[$expectedKey],
                    sprintf('Failed asserting that array key "%s" matches expected value.', $expectedKey)
                );
                return true;
            }
        );
    }
}
