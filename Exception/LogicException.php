<?php

namespace Oro\Bundle\PayPalExpressBundle\Exception;

/**
 * Could be useful to catch only bundle LogicExceptions
 */
class LogicException extends \LogicException implements ExceptionInterface
{
}
