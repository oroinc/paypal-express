<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport\Exception;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\ErrorInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalSDKObjectTranslatorInterface;
use PayPal\Exception\PayPalConnectionException;

/**
 * Extracts error information from PayPal PHP SDK exception ({@see PayPalConnectionException}), formats it and creates
 * {@see TransportException} using that information to generate exception's message and error context.
 */
class TransportExceptionFactory implements TransportExceptionFactoryInterface
{
    const MESSAGE_PARTS_DELIMITER = '. ';

    /**
     * @var PayPalSDKObjectTranslatorInterface
     */
    protected $translator;

    public function __construct(PayPalSDKObjectTranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[\Override]
    public function createTransportException($message, Context $errorContext, \Throwable $throwable = null)
    {
        $errorInfo = $this->getErrorInfo($throwable);
        $message = $this->processMessage($message, $throwable, $errorInfo);
        $errorContext->addErrorInfo($errorInfo);

        return new TransportException($message, $errorContext->getContext(), $throwable);
    }

    /**
     * @param \Throwable|null $throwable
     * @return ErrorInfo
     */
    protected function getErrorInfo(\Throwable $throwable = null)
    {
        if ($throwable instanceof PayPalConnectionException) {
            return $this->translator->getErrorInfo($throwable);
        }
        return null;
    }

    /**
     * @param string          $message
     * @param \Throwable|null $throwable
     * @param ErrorInfo|null  $errorInfo
     * @return array
     */
    protected function processMessage(
        $message,
        \Throwable $throwable = null,
        ErrorInfo $errorInfo = null
    ) {
        $reason = null;
        if ($errorInfo) {
            $reason = $this->getReasonByErrorInfo($errorInfo);
        } elseif ($throwable && $throwable->getMessage()) {
            $reason = $throwable->getMessage();
        }
        if ($reason && !$message) {
            $message = $reason;
        } elseif ($message && $reason) {
            $message = sprintf(
                '%s. Reason: %s',
                rtrim($message, '.'),
                $reason
            );
        }
        return $message;
    }

    /**
     * @param ErrorInfo $errorInfo
     * @return string
     */
    public function getReasonByErrorInfo(ErrorInfo $errorInfo)
    {
        return $this->createExceptionMessageByParts(
            $errorInfo->getMessage(),
            [
                'Error Name'       => $errorInfo->getName(),
                'Information Link' => $errorInfo->getInformationLink()
            ]
        );
    }

    /**
     * @param string $messagePrefix 'Payment failed'
     * @param array  $messageParts ['Error Name' => 'AMOUNT_MISMATCH', 'Information Link' => 'https://site.com/#errors']
     * @return string 'Payment failed. Error Name: AMOUNT_MISMATCH. Information Link: https://site.com/#errors'
     */
    protected function createExceptionMessageByParts($messagePrefix, array $messageParts)
    {
        $resultMessageParts = [];
        if ($messagePrefix) {
            $resultMessageParts = [$messagePrefix];
        }

        $messageParts = array_filter($messageParts);
        foreach ($messageParts as $title => $text) {
            $messagePart = sprintf('%s: %s', $title, $text);
            $resultMessageParts[] = $messagePart;
        }

        return self::concatMessageParts($resultMessageParts);
    }

    /**
     * @param array $messageParts
     * @return string
     */
    protected static function concatMessageParts(array $messageParts)
    {
        $resultMessage = '';
        foreach ($messageParts as $messagePart) {
            $resultMessage .= rtrim($messagePart, static::MESSAGE_PARTS_DELIMITER);
            $resultMessage .= static::MESSAGE_PARTS_DELIMITER;
        }
        return trim($resultMessage);
    }
}
