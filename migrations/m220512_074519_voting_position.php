<?php

use yii\db\Migration;

class m220512_074519_voting_position extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('votingBlock', 'position', 'INTEGER NOT NULL DEFAULT 0 AFTER consultationId');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('votingBlock', 'position');
    }
}
