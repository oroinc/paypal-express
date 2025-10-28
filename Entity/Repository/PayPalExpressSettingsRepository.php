<?php

namespace Oro\Bundle\PayPalExpressBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\PayPalExpressBundle\Entity\PayPalExpressSettings;
use Oro\Bundle\PayPalExpressBundle\Integration\PayPalExpressChannelType;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Extends base repository and adds additional methods to get {@see PayPalExpressSettings} entity.
 */
class PayPalExpressSettingsRepository extends ServiceEntityRepository
{
    private ?AclHelper $aclHelper = null;

    public function setAclHelper(AclHelper $aclHelper): self
    {
        $this->aclHelper = $aclHelper;

        return $this;
    }

    /**
     * @return PayPalExpressSettings[]
     */
    public function getEnabledIntegrationsSettings()
    {
        $qb = $this->createQueryBuilder('settings')
            ->innerJoin('settings.channel', 'channel')
            ->andWhere('channel.enabled = true')
            ->andWhere('channel.type = :type')
            ->orderBy('settings.id')
            ->setParameter('type', PayPalExpressChannelType::TYPE);

        return $this->aclHelper?->apply($qb)->getResult();
    }
}
