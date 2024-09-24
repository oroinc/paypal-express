<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\Config;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Entity\Repository\PayPalExpressSettingsRepository;
use Psr\Log\LoggerInterface;

/**
 * Provides instances of {@see PayPalExpressConfigInterface} for {@see PayPalExpressMethodProvider}.
 */
class PayPalExpressConfigProvider implements PayPalExpressConfigProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PayPalExpressConfigFactoryInterface
     */
    protected $factory;

    /**
     * @var array|null
     */
    protected $configs = null;

    public function __construct(
        ManagerRegistry $doctrine,
        LoggerInterface $logger,
        PayPalExpressConfigFactoryInterface $factory
    ) {
        $this->doctrine = $doctrine;
        $this->logger   = $logger;
        $this->factory  = $factory;
    }

    #[\Override]
    public function getPaymentConfigs()
    {
        if ($this->configs == null) {
            $this->configs = $this->collectConfigs();
        }

        return $this->configs;
    }

    /**
     * @return PayPalExpressConfigInterface[]
     */
    protected function collectConfigs()
    {
        $configs = [];

        try {
            /** @var PayPalExpressSettingsRepository $repository */
            $repository = $this->doctrine->getRepository(PayPalExpressSettings::class);

            $settings = $repository->getEnabledIntegrationsSettings();
            foreach ($settings as $setting) {
                $config = $this->factory->createConfig($setting);
                $configs[$config->getPaymentMethodIdentifier()] = $config;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
        }

        return $configs;
    }
}
