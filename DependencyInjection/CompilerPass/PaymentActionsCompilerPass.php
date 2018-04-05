<?php

namespace Oro\Bundle\PayPalExpressBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers payment action services ({@see PaymentActionInterface}) in registry services {@see PaymentActionRegistry}.
 */
class PaymentActionsCompilerPass implements CompilerPassInterface
{
    const PAYMENT_ACTIONS_REGISTRY_SERVICE_ID = 'oro_paypal_express.method.payment_action.registry';
    const PAYMENT_ACTIONS_TAG_NAME = 'oro_paypal_express.payment_action';

    const COMPLETE_PAYMENT_ACTIONS_REGISTRY_SERVICE_ID = 'oro_paypal_express.method.payment_action.complete.registry';
    const COMPLETE_PAYMENT_ACTIONS_TAG_NAME = 'oro_paypal_express.complete_payment_action';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerPaymentActionsByTags(
            $container,
            self::PAYMENT_ACTIONS_REGISTRY_SERVICE_ID,
            self::PAYMENT_ACTIONS_TAG_NAME
        );

        $this->registerPaymentActionsByTags(
            $container,
            self::COMPLETE_PAYMENT_ACTIONS_REGISTRY_SERVICE_ID,
            self::COMPLETE_PAYMENT_ACTIONS_TAG_NAME
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $registryServiceId
     * @param string           $paymentActionServiceTag
     */
    private function registerPaymentActionsByTags(
        ContainerBuilder $container,
        $registryServiceId,
        $paymentActionServiceTag
    ) {
        $registryDefinition = $container->findDefinition($registryServiceId);
        $taggedServicesData = $container->findTaggedServiceIds($paymentActionServiceTag);
        $paymentActionServicesIds = array_keys($taggedServicesData);
        $this->registerPaymentActionServices($registryDefinition, $paymentActionServicesIds);
    }

    /**
     * @param Definition $registryDefinition
     * @param string[]   $paymentActionServicesIds
     */
    private function registerPaymentActionServices(Definition $registryDefinition, array $paymentActionServicesIds)
    {
        foreach ($paymentActionServicesIds as $paymentActionServiceId) {
            $taggedServiceReference = new Reference($paymentActionServiceId);
            $registryDefinition->addMethodCall('registerAction', [$taggedServiceReference]);
        }
    }
}
