<?php

namespace Oro\Bundle\PayPalExpressBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class PaymentActionsCompilerPass implements CompilerPassInterface
{
    const PAYMENT_ACTIONS_REGISTRY_SERVICE_ID = 'oro_paypal_express.method.payment_action.registry';
    const PAYMENT_ACTIONS_TAG_NAME = 'oro_paypal_express.payment_action';

    const COMPLETE_PAYMENT_ACTIONS_REGISTRY_SERVICE_ID = 'oro_paypal_express.method.payment_action.complete.registry';
    const COMPLETE_PAYMENT_ACTIONS_TAG_NAME = 'oro_paypal_express.compleate_payment_action';



    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServicesData = $container->findTaggedServiceIds(self::PAYMENT_ACTIONS_TAG_NAME);
        $registryDefinition = $container->findDefinition(self::PAYMENT_ACTIONS_REGISTRY_SERVICE_ID);
        $this->registerServices($registryDefinition, $taggedServicesData);

        $taggedServicesData = $container->findTaggedServiceIds(self::COMPLETE_PAYMENT_ACTIONS_TAG_NAME);
        $registryDefinition = $container->findDefinition(self::COMPLETE_PAYMENT_ACTIONS_REGISTRY_SERVICE_ID);
        $this->registerServices($registryDefinition, $taggedServicesData);
    }

    /**
     * @param Definition $registryDefinition
     * @param array      $taggedServicesData
     */
    protected function registerServices(Definition $registryDefinition, array $taggedServicesData)
    {
        foreach ($taggedServicesData as $serviceId => $tags) {
            $taggedServiceReference = new Reference($serviceId);
            $registryDefinition->addMethodCall('registerAction', [$taggedServiceReference]);
        }
    }
}
