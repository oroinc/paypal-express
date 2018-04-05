<?php

namespace Oro\Bundle\PayPalExpressBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PayPalExpressBundle\Migrations\Schema\v1_0;

/**
 * Installer for {@see OroPayPalExpress} bundle.
 */
class OroPayPalExpressBundleInstaller implements Installation
{

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $createPayPalSettings = new v1_0\CreatePayPalExpressSettings();
        $createPayPalSettings->up($schema, $queries);
    }
}
