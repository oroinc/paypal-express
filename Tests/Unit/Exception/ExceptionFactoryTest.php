<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Exception;

use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Exception\LogicException;
use Oro\Bundle\PayPalExpressBundle\Exception\RuntimeException;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedCurrencyException;
use Oro\Bundle\PayPalExpressBundle\Exception\UnsupportedValueException;
use Oro\Bundle\PayPalExpressBundle\Transport\SupportedCurrenciesHelper;

class ExceptionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExceptionFactory */
    private $factory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SupportedCurrenciesHelper */
    private $supportedCurrenciesHelper;

    protected function setUp(): void
    {
        $this->supportedCurrenciesHelper = $this->createMock(SupportedCurrenciesHelper::class);

        $this->factory = new ExceptionFactory($this->supportedCurrenciesHelper);
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
