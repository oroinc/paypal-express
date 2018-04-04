<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

/**
 * Represents public interface of Payment Actions Provider
 */
interface ActionNamesProviderInterface
{
    /**
     * @return string[]
     */
    public function getActionNames();
}
