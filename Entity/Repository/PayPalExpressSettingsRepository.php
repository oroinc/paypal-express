<?php

namespace Oro\Bundle\PayPalExpressBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Integration\PayPalExpressChannelType;

/**
 * Responsible for:
 *   - retrieve enabled integrations settings
 */
class PayPalExpressSettingsRepository extends EntityRepository
{
    /**
     * @return PayPalExpressSettings[]
     */
    public function getEnabledIntegrationsSettings()
    {
        return $this->createQueryBuilder('settings')
            ->innerJoin('settings.channel', 'channel')
            ->andWhere('channel.enabled = true')
            ->andWhere('channel.type = :type')
            ->orderBy('settings.id')
            ->setParameter('type', PayPalExpressChannelType::TYPE)
            ->getQuery()
            ->getResult();
    }
}
