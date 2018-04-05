<?php

namespace Oro\Bundle\PayPalExpressBundle;

use Oro\Bundle\PayPalExpressBundle\DependencyInjection\CompilerPass\PaymentActionsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Represents a bundle class for PayPal Express Payment Method.
 *
 * Depends on {@see \Oro\Bundle\IntegrationBundle\OroIntegrationBundle} and
 * {@see \Oro\Bundle\PaymentBundle\OroPaymentBundle}.
 */
class OroPayPalExpressBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new PaymentActionsCompilerPass());
    }
}
