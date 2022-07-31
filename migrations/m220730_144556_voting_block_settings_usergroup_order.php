<?php

use yii\db\Migration;

class m220730_144556_voting_block_settings_usergroup_order extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('votingBlock', 'settings', 'TEXT NULL DEFAULT NULL');
        $this->addColumn('consultationUserGroup', 'position', 'INT NOT NULL DEFAULT 0 AFTER siteId');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('consultationUserGroup', 'position');
        $this->dropColumn('votingBlock', 'settings');
    }
}
