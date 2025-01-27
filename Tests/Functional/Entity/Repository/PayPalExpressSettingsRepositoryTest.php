<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Entity\Repository\PayPalExpressSettingsRepository;
use Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
        $this->loadFixtures([LoadChannelData::class]);

        $this->repository = self::getContainer()->get('doctrine')->getRepository(PayPalExpressSettings::class);
    }

    public function testGetEnabledIntegrationsSettings(): void
    {
        self::assertEquals(
            [
                $this->getReference('oro_paypal_express.settings.foo'),
                $this->getReference('oro_paypal_express.settings.baz')
            ],
            $this->repository->getEnabledIntegrationsSettings()
        );
    }
}
