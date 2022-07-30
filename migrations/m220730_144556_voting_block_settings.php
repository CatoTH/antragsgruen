<?php

use yii\db\Migration;

class m220730_144556_voting_block_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('votingBlock', 'settings', 'TEXT NULL DEFAULT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('votingBlock', 'settings');
    }
}
