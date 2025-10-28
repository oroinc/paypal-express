<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Entity\Repository\PayPalExpressSettingsRepository;
use Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

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

        $this->loadFixtures([LoadChannelData::class]);
    }

    public function testGetEnabledIntegrationsSettings()
    {
        $userManager = self::getContainer()->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);

        $token = new UsernamePasswordOrganizationToken(
            $admin,
            'admin',
            'main',
            $admin->getOrganization(),
            $admin->getRoles()
        );

        $this->getContainer()->get('security.token_storage')->setToken($token);

        $settings = $this->repository->getEnabledIntegrationsSettings();

        $expected = [
            $this->getReference('oro_paypal_express.settings.foo'),
            $this->getReference('oro_paypal_express.settings.bar')
        ];

        $this->assertEquals($expected, $settings);
    }
}
