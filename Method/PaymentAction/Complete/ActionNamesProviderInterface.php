<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction\Complete;

/**
 * Provide names of complete actions for {@see PayPalExpressSettingsType}.
 */
interface ActionNamesProviderInterface
{
    /**
     * @return string[]
     */
    public function getActionNames();
}
