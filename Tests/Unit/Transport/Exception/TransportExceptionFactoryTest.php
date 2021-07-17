<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Exception;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ErrorInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\Context;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\TransportException;
use Oro\Bundle\PayPalExpressBundle\Transport\Exception\TransportExceptionFactory;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalSDKObjectTranslatorInterface;
use PayPal\Exception\PayPalConnectionException;

class TransportExceptionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TransportExceptionFactory
     */
    protected $factory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|PayPalSDKObjectTranslatorInterface
     */
    protected $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(PayPalSDKObjectTranslatorInterface::class);
        $this->factory = new TransportExceptionFactory($this->translator);
    }

    /**
     * @dataProvider connectionExceptionDataProvider
     * @param string $errorInfoMessage
     * @param string $errorInfoErrorCode
     * @param string $errorInfoLink
     * @param string $expectedMessage
     */
    public function testCanCreateTransportExceptionFromConnectionPreviousException(
        $mainMessage,
        $errorInfoMessage,
        $errorInfoErrorCode,
        $errorInfoLink,
        $expectedMessage
    ) {
        $payPalConnectionException = new PayPalConnectionException('', '');
        $errorInfo = $this->createErrorInfo(
            $errorInfoMessage,
            $errorInfoErrorCode,
            $errorInfoLink
        );

        $this->translator->expects($this->once())
            ->method('getErrorInfo')
            ->with($payPalConnectionException)
            ->willReturn($errorInfo);

        $actualException = $this->factory->createTransportException(
            $mainMessage,
            new Context(),
            $payPalConnectionException
        );

        $this->assertInstanceOf(TransportException::class, $actualException);
        $this->assertEquals($expectedMessage, $actualException->getMessage());
        $this->assertEquals($payPalConnectionException, $actualException->getPrevious());
    }

    /**
     * @return array
     */
    public function connectionExceptionDataProvider()
    {
        $mainMessage = 'Cannot process payment.';
        $reason = 'Order is already voided, expired, or completed.';
        $errorName = 'ORDER_ALREADY_COMPLETED';
        $infoLink = 'https://developer.paypal.com/docs/api/payments/#errors';

        return [
            'exception info has message, error name, and info link' => [
                'message'          => $mainMessage,
                'error_message'    => $reason,
                'error_name'       => $errorName,
                'information_link' => $infoLink,
                'expectedMessage'  => "{$mainMessage} Reason: {$reason} Error Name: {$errorName}. " .
                    "Information Link: {$infoLink}."
            ],
            'exception info has message and error name'             => [
                'message'          => $mainMessage,
                'error_message'    => $reason,
                'error_name'       => $errorName,
                'information_link' => null,
                'expectedMessage'  => "{$mainMessage} Reason: {$reason} Error Name: {$errorName}."
            ],
            'exception info has only message'                       => [
                'message'          => $mainMessage,
                'error_message'    => $reason,
                'error_name'       => null,
                'information_link' => null,
                'expectedMessage'  => "{$mainMessage} Reason: {$reason}"
            ],
            'exception info has only error name'                    => [
                'message'          => $mainMessage,
                'error_message'    => null,
                'error_name'       => $errorName,
                'information_link' => null,
                'expectedMessage'  => "{$mainMessage} Reason: Error Name: {$errorName}."
            ],
            'exception has only main message'                       => [
                'message'          => $mainMessage,
                'error_message'    => null,
                'error_name'       => null,
                'information_link' => null,
                'expectedMessage'  => $mainMessage
            ],
            'exception has only exception info reason'              => [
                'message'          => null,
                'error_message'    => $reason,
                'error_name'       => null,
                'information_link' => null,
                'expectedMessage'  => $reason
            ],
            'exception has only exception info reason and name'     => [
                'message'          => null,
                'error_message'    => $reason,
                'error_name'       => $errorName,
                'information_link' => null,
                'expectedMessage'  => "{$reason} Error Name: {$errorName}."
            ],
        ];
    }

    /**
     * @param string $message
     * @param string $statusCode
     * @param string $infoLink
     * @return ErrorInfo
     */
    protected function createErrorInfo($message, $statusCode, $infoLink)
    {
        return new ErrorInfo(
            $message,
            $statusCode,
            '',
            $infoLink,
            ''
        );
    }

    /**
     * @dataProvider otherPreviousExceptionDataProvider
     * @param string $mainMessage
     * @param string $exceptionMessage
     * @param        $expectedMessage
     */
    public function testCanCreateTransportExceptionFromPreviousException(
        $mainMessage,
        $exceptionMessage,
        $expectedMessage
    ) {
        $previousException = new \Exception($exceptionMessage);

        $actualException = $this->factory->createTransportException(
            $mainMessage,
            new Context(),
            $previousException
        );

        $this->assertInstanceOf(TransportException::class, $actualException);
        $this->assertEquals($expectedMessage, $actualException->getMessage());
        $this->assertEquals($previousException, $actualException->getPrevious());
    }

    /**
     * @return array
     */
    public function otherPreviousExceptionDataProvider()
    {
        $mainMessage = 'Cannot process payment.';
        $previousExceptionMessage = 'Internal error.';

        return [
            'previous exception has message'    => [
                'message'                     => $mainMessage,
                'previous_exception_messsage' => $previousExceptionMessage,
                'expectedMessage'             => "{$mainMessage} Reason: {$previousExceptionMessage}"
            ],
            'previous exception has no message' => [
                'message'                     => $mainMessage,
                'previous_exception_messsage' => null,
                'expectedMessage'             => "{$mainMessage}"
            ],
        ];
    }

    /**
     * @dataProvider otherPreviousErrorDataProvider
     * @param string $mainMessage
     * @param string $errorMessage
     * @param        $expectedMessage
     */
    public function testCanCreateTransportExceptionFromPreviousError(
        $mainMessage,
        $errorMessage,
        $expectedMessage
    ) {
        $previousError = new \Error($errorMessage);

        $actualException = $this->factory->createTransportException(
            $mainMessage,
            new Context(),
            $previousError
        );

        $this->assertInstanceOf(TransportException::class, $actualException);
        $this->assertEquals($expectedMessage, $actualException->getMessage());
        $this->assertEquals($previousError, $actualException->getPrevious());
    }

    /**
     * @return array
     */
    public function otherPreviousErrorDataProvider()
    {
        $mainMessage = 'Cannot process payment.';
        $previousExceptionMessage = 'Internal error.';

        return [
            'previous exception has message'    => [
                'message'                 => $mainMessage,
                'previous_error_messsage' => $previousExceptionMessage,
                'expectedMessage'         => "{$mainMessage} Reason: {$previousExceptionMessage}"
            ],
            'previous exception has no message' => [
                'message'                 => $mainMessage,
                'previous_error_messsage' => null,
                'expectedMessage'         => "{$mainMessage}"
            ],
        ];
    }

    public function testCanCreateTransportExceptionWithoutPreviousException()
    {
        $mainMessage = 'Cannot process payment.';

        $actualException = $this->factory->createTransportException($mainMessage, new Context());

        $this->assertInstanceOf(TransportException::class, $actualException);
        $this->assertEquals($mainMessage, $actualException->getMessage());
    }

    public function testCanProcessErrorInfoAndAddToContext()
    {
        $mainMessage = 'Cannot process payment.';
        $reason = 'Order is already voided, expired, or completed.';
        $errorName = 'ORDER_ALREADY_COMPLETED';
        $infoLink = 'https://developer.paypal.com/docs/api/payments/#errors';

        $errorInfoArray = [
            'message'          => $mainMessage,
            'error_message'    => $reason,
            'error_name'       => $errorName,
            'information_link' => $infoLink,
        ];

        $expectedContext = [
            'error_info' => $errorInfoArray
        ];

        $errorInfo = $this->createMock(ErrorInfo::class);
        $errorInfo->expects($this->once())
            ->method('toArray')
            ->willReturn($errorInfoArray);

        $payPalConnectionException = new PayPalConnectionException('', '');

        $this->translator->expects($this->once())
            ->method('getErrorInfo')
            ->with($payPalConnectionException)
            ->willReturn($errorInfo);

        $actualException = $this->factory->createTransportException(
            '',
            new Context(),
            $payPalConnectionException
        );

        $this->assertInstanceOf(TransportException::class, $actualException);
        $actualContext = $actualException->getErrorContext();
        $this->assertEquals($expectedContext, $actualContext);
    }
}
