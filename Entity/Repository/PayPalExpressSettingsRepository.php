<?php

namespace Oro\Bundle\PayPalExpressBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Integration\PayPalChannelType;

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
            ->setParameter('type', PayPalChannelType::TYPE)
            ->getQuery()
            ->getResult();
    }
}
