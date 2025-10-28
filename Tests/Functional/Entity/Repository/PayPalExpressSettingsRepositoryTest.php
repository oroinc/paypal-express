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

    protected function setUp(): void
    {
        $this->initClient();

        $mangerRegistry = $this->getContainer()->get('doctrine');

        $repository = $mangerRegistry->getRepository(PayPalExpressSettings::class);

        /**
         * Guard Assertion
         */
        $this->assertInstanceOf(PayPalExpressSettingsRepository::class, $repository);

        $this->repository = $repository;

        $this->loadFixtures([LoadChannelData::class, LoadOrganization::class]);
    }

    public function testGetEnabledIntegrationsSettings()
    {
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $adminToken = new UsernamePasswordOrganizationToken(
            $this->getReference(LoadUser::USER),
            'key',
            $organization
        );

        $this->getContainer()->get('security.token_storage')->setToken($adminToken);

        $settings = $this->repository->getEnabledIntegrationsSettings();

        $expected = [
            $this->getReference('oro_paypal_express.settings.foo'),
            $this->getReference('oro_paypal_express.settings.bar')
        ];

        $this->assertEquals($expected, $settings);
    }
}
