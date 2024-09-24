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
        $settings = $this->repository->getEnabledIntegrationsSettings();

        $expected = [
            $this->getReference('oro_paypal_express.settings.foo'),
            $this->getReference('oro_paypal_express.settings.baz')
        ];

        $this->assertEquals($expected, $settings);
    }
}
