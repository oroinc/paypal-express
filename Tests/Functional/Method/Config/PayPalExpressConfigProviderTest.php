<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Functional\Method\Config;

use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigProvider;
use Oro\Bundle\PayPalExpressBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class PayPalExpressConfigProviderTest extends WebTestCase
{
    private PayPalExpressConfigProvider $payPalExpressConfigProvider;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadChannelData::class]);

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
                'clientSecret' => 'NxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
                'clientId'     => 'KxBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ',
                'label'        => 'baz label',
                'adminLabel'   => 'bar channel',
                'shortLabel'   => 'baz short label',
            ],
        ];

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
