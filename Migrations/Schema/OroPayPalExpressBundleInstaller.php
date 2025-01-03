<?php

namespace Oro\Bundle\PayPalExpressBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPayPalExpressBundleInstaller implements Installation
{
    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_0';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->updateOroIntegrationTransportTable($schema);

        $this->createPpExpressLabelTable($schema);
        $this->createPpExpressShortLabelTable($schema);

        $this->addPpExpressLabelForeignKeys($schema);
        $this->addPpExpressShortLabelForeignKeys($schema);
    }

    private function updateOroIntegrationTransportTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('pp_express_client_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_express_client_secret', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_express_sandbox_mode', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_express_payment_action', 'string', ['notnull' => false, 'length' => 255]);
    }

    /**
     * Create oro_pp_express_label table
     */
    private function createPpExpressLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_pp_express_label');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_A5EC4163EB576E89');
        $table->addIndex(['transport_id'], 'IDX_A5EC41639909C13F');
    }

    /**
     * Create oro_pp_express_short_label table
     */
    private function createPpExpressShortLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_pp_express_short_label');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_3E6DC779EB576E89');
        $table->addIndex(['transport_id'], 'IDX_3E6DC7799909C13F');
    }

    /**
     * Add oro_pp_express_label foreign keys.
     */
    private function addPpExpressLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_pp_express_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_pp_express_short_label foreign keys.
     */
    private function addPpExpressShortLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_pp_express_short_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
