<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Exception;

use Oro\Bundle\PayPalExpressBundle\Exception\ConnectionException;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\LogicException;
use Oro\Bundle\PayPalExpressBundle\Exception\OperationExecutionFailedException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedCurrencyException;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedValueException;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ExceptionInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\DTO\PaymentInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;
use PayPal\Exception\PayPalConnectionException;

class ExceptionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExceptionFactory
     */
    protected $factory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SupportedCurrenciesHelper
     */
    protected $supportedCurrenciesHelper;

    protected function setUp()
    {
        $this->supportedCurrenciesHelper = $this->createMock(SupportedCurrenciesHelper::class);

        $this->factory = new ExceptionFactory($this->supportedCurrenciesHelper);
    }

    public function testCreateConnectionException()
    {
        $message = 'Could not Capture payment.';

        $expectedReason = 'Order is already voided, expired, or completed.';
        $expectedStatusCode = 'ORDER_ALREADY_COMPLETED';
        $expectedLink = 'https://developer.paypal.com/docs/api/payments/#errors';

        $paymentInfo = $this->createMock(PaymentInfo::class);

        $exceptionInfo = new ExceptionInfo(
            $expectedReason,
            $expectedStatusCode,
            '',
            $expectedLink,
            '',
            $paymentInfo
        );

        $previousException = new PayPalConnectionException('', '');

        $expectedMessage = "{$message}. Reason: {$expectedReason}, Code: {$expectedStatusCode}," .
            " Information Link: {$expectedLink}";
        $expectedException = new ConnectionException($expectedMessage, 0, $previousException);
        $expectedException->setExceptionInfo($exceptionInfo);

        $actualConnectionException = $this->factory
            ->createConnectionException($message, $exceptionInfo, $previousException);

        $this->assertEquals($expectedException, $actualConnectionException);
    }

    public function testCreateOperationExecutionFailedException()
    {
        $message = 'Could not execute payment.';

        $expectedReason = 'UNABLE_TO_COMPLETE_TRANSACTION';

        $expectedMessage = "{$message} Reason: {$expectedReason}.";
        $expectedException = new OperationExecutionFailedException($expectedMessage);

        $actualException = $this->factory
            ->createOperationExecutionFailedException($message, $expectedReason);

        $this->assertEquals($expectedException, $actualException);
    }

    public function testCreateUnsupportedCurrencyException()
    {
        $currency = 'UNKNOWN';

        $this->supportedCurrenciesHelper->expects($this->once())
            ->method('getSupportedCurrencyCodes')
            ->willReturn(['USD', 'EUR']);

        $expectedMessage = 'Currency "UNKNOWN" is not supported. Only next currencies are supported: "USD, EUR"';
        $expectedException = new UnsupportedCurrencyException($expectedMessage);

        $actualException = $this->factory->createUnsupportedCurrencyException($currency);

        $this->assertEquals($expectedException, $actualException);
    }

    public function testCreateUnsupportedValueException()
    {
        $expectedMessage = 'Unsupported decimal amount for currency "JPY"';
        $expectedException = new UnsupportedValueException($expectedMessage);

        $actualException = $this->factory->createUnsupportedValueException($expectedMessage);

        $this->assertEquals($expectedException, $actualException);
    }

    public function testCreateLogicException()
    {
        $expectedMessage = 'Payment Action with the same name is already registered';
        $expectedException = new LogicException($expectedMessage);

        $actualException = $this->factory->createLogicException($expectedMessage);

        $this->assertEquals($expectedException, $actualException);
    }

    public function testCreateRuntimeException()
    {
        $expectedMessage = 'Payment Action is not supported';
        $expectedException = new RuntimeException($expectedMessage);

        $actualException = $this->factory->createRuntimeException($expectedMessage);

        $this->assertEquals($expectedException, $actualException);
    }
}
