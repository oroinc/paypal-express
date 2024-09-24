<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Integration\PayPalExpressChannelType;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class LoadChannelData extends AbstractFixture implements DependentFixtureInterface
{
    private array $data = [
        [
            'name' => 'foo channel',
            'transport' => 'oro_paypal_express.settings.foo',
            'reference' => 'oro_paypal_express.channel.foo'
        ],
        [
            'name' => 'bar channel',
            'transport' => 'oro_paypal_express.settings.baz',
            'reference' => 'oro_paypal_express.channel.bar'
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadPayPalExpressSettingsData::class, LoadUser::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        foreach ($this->data as $item) {
            $channel = new Channel();
            $channel->setOrganization($user->getOrganization());
            $channel->setDefaultUserOwner($user);
            $channel->setType(PayPalExpressChannelType::TYPE);
            $channel->setName($item['name']);
            $channel->setEnabled(true);

            /** @var PayPalExpressSettings $transport */
            $transport = $this->getReference($item['transport']);
            $transport->setChannel($channel);
            $channel->setTransport($transport);

            $manager->persist($channel);
            $this->setReference($item['reference'], $channel);
        }
        $manager->flush();
    }
}
