<?php

namespace Oro\Bundle\PayPalExpressBundle\Exception;

/**
 * Exception for using the method with currency which is not supporting decimal amount.
 */
class UnsupportedValueException extends LogicException
{
}
