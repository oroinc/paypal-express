<?php

namespace Oro\Bundle\PayPalExpressBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PayPalBundle\Migrations\Schema\v1_0\CreatePayPalSettings;

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
        $createPayPalSettings = new CreatePayPalSettings();
        $createPayPalSettings->up($schema, $queries);
    }
}
