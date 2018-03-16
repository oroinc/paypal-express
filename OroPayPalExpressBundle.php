<?php

namespace Oro\Bundle\PayPalExpressBundle;

use Oro\Bundle\PayPalExpressBundle\DependencyInjection\CompilerPass\PaymentActionsCompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroPayPalExpressBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new PaymentActionsCompilerPass());
    }
}
