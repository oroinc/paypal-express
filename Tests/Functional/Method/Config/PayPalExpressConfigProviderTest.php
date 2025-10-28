<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\Method\Config;

use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigProvider;
use Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

/**
 * @dbIsolationPerTest
 */
class PayPalExpressConfigProviderTest extends WebTestCase
{
    private PayPalExpressConfigProvider $payPalExpressConfigProvider;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadChannelData::class, LoadOrganization::class]);

        $this->payPalExpressConfigProvider = $this->getContainer()->get('oro_paypal_express.method.config.provider');
    }

    public function testGetPaymentConfigs()
    {
        $expectedConfigs = [
            [
                'clientSecret' => 'TxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
                'clientId'     => 'YxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
                'label'        => 'foo label',
                'adminLabel'   => 'foo channel',
                'shortLabel'   => 'foo short label',
            ],
            [
                'clientSecret' => 'LxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
                'clientId'     => 'ZxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
                'label'        => 'bar label',
                'adminLabel'   => 'bar channel',
                'shortLabel'   => 'bar short label'
            ],
        ];

        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $adminToken = new UsernamePasswordOrganizationToken(
            $this->getReference(LoadUser::USER),
            'key',
            $organization
        );

        $this->getContainer()->get('security.token_storage')->setToken($adminToken);

        $paymentConfigs = $this->payPalExpressConfigProvider->getPaymentConfigs();

        $actualConfigs = [];
        foreach ($paymentConfigs as $payPalExpressConfig) {
            $actualConfig = [
                'clientSecret' => $payPalExpressConfig->getClientSecret(),
                'clientId'     => $payPalExpressConfig->getClientId(),
                'label'        => $payPalExpressConfig->getLabel(),
                'adminLabel'   => $payPalExpressConfig->getAdminLabel(),
                'shortLabel'   => $payPalExpressConfig->getShortLabel(),
            ];
            $actualConfigs[] = $actualConfig;
        }

        $this->assertEquals($expectedConfigs, $actualConfigs);
    }
}
