<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Integration\PayPalExpressChannelType;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadChannelData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $data = [
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

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);

        foreach ($this->data as $item) {
            $channel = new Channel();
            $channel->setOrganization($admin->getOrganization());
            $channel->setDefaultUserOwner($admin);
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

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPayPalExpressSettingsData::class
        ];
    }
}
