<?php

use yii\db\Migration;

class m220305_160942_voting_quorum extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('votingBlock', 'quorumType', 'TINYINT NULL DEFAULT NULL AFTER majorityType');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('votingBlock', 'quorumType');
    }
}
