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

        $this->createTable('votingQuestion', [
            'id' => 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'consultationId' => 'INTEGER NOT NULL',
            'title' => 'TEXT NOT NULL',
            'votingStatus' => 'TINYINT DEFAULT NULL',
            'votingBlockId' => 'INTEGER DEFAULT NULL',
            'votingData' => 'TEXT NULL DEFAULT NULL',
        ]);
        $this->addForeignKey('fk_question_block', 'votingQuestion', 'votingBlockId', 'votingBlock', 'id');
        $this->addForeignKey('fk_question_consultation', 'votingQuestion', 'consultationId', 'consultation', 'id');

        $this->addColumn('vote', 'questionId', 'INTEGER NULL DEFAULT NULL AFTER amendmentId');
        $this->addForeignKey('fk_vote_question', 'vote', 'questionId', 'votingQuestion', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_vote_question', 'vote');
        $this->dropColumn('vote', 'questionId');

        $this->dropForeignKey('fk_question_consultation', 'votingQuestion');
        $this->dropForeignKey('fk_question_block', 'votingQuestion');
        $this->dropTable('votingQuestion');

        $this->dropColumn('votingBlock', 'policyVote');
        $this->dropColumn('votingBlock', 'answers');
    }
}
