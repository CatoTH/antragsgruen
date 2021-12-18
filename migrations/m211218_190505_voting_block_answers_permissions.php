<?php

use yii\db\Migration;

class m211218_190505_voting_block_answers_permissions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('votingBlock', 'answers', 'TEXT NULL DEFAULT NULL AFTER usersPresentByOrga');
        $this->addColumn('votingBlock', 'policyVote', 'TEXT NULL DEFAULT NULL AFTER answers');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('votingBlock', 'answers');
        $this->dropColumn('votingBlock', 'policyVote');
    }
}
