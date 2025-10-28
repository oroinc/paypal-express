<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Entity\Repository\PayPalExpressSettingsRepository;
use Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

/**
 * @dbIsolationPerTest
 */
class PayPalExpressSettingsRepositoryTest extends WebTestCase
{
    private PayPalExpressSettingsRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadChannelData::class, LoadOrganization::class]);

        $this->repository = self::getContainer()->get('doctrine')->getRepository(PayPalExpressSettings::class);
    }

    public function testGetEnabledIntegrationsSettings(): void
    {
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $adminToken = new UsernamePasswordOrganizationToken(
            $this->getReference(LoadUser::USER),
            'key',
            $organization
        );

        $this->getContainer()->get('security.token_storage')->setToken($adminToken);

        self::assertEquals(
            [
                $this->getReference('oro_paypal_express.settings.foo'),
                $this->getReference('oro_paypal_express.settings.bar')
            ],
            $this->repository->getEnabledIntegrationsSettings()
        );
    }
}
