<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Tests\Functional\Entity\DataFixtures\LoadPaymentMethodsConfigsRuleData as BaseFixture;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadPayPalExpressPaymentMethodData extends BaseFixture
{
    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(
            parent::getDependencies(),
            [LoadChannelData::class]
        );
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        parent::load($manager);

        $methodConfig = new PaymentMethodConfig();
        $methodConfig->setType($this->getPaymentMethodIdentifier($this->container));

        /** @var PaymentMethodsConfigsRule $methodsConfigsRule */
        $methodsConfigsRule = $this->getReference('payment.payment_methods_configs_rule.1');
        $methodsConfigsRule->addMethodConfig($methodConfig);

        $manager->flush();
    }

    private function getPaymentMethodIdentifier(ContainerInterface $container): string
    {
        return $container->get('oro_paypal_express.method.generator.identifier')
            ->generateIdentifier($this->getReference('oro_paypal_express.channel.foo'));
    }
}
