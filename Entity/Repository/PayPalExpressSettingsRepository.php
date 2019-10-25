<?php

namespace Oro\Bundle\PayPalExpressBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Integration\PayPalExpressChannelType;

/**
 * Extends base repository and adds additional methods to get {@see PayPalExpressSettings} entity.
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
