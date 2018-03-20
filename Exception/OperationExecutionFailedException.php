<?php

namespace Oro\Bundle\PayPalExpressBundle\Exception;

class OperationExecutionFailedException extends RuntimeException
{
    /**
     * @param string $operationName
     * @param string $relatedEntityIdentity
     * @param string $relatedEntityStatus
     * @param string $failureReason
     *
     * @return OperationExecutionFailedException
     */
    public static function create($operationName, $relatedEntityIdentity, $relatedEntityStatus, $failureReason = null)
    {
        if ($failureReason) {
            $message = sprintf(
                'Could not complete "%s" for Entity[id: %s, status: %s]. Reason: %s',
                $operationName,
                $relatedEntityIdentity,
                $relatedEntityStatus,
                $failureReason
            );
        } else {
            $message = sprintf(
                'Could not complete "%s" for Entity[id: %s, status: %s].',
                $operationName,
                $relatedEntityIdentity,
                $relatedEntityStatus
            );
        }

        return new OperationExecutionFailedException($message);
    }
}
