<?php

use yii\db\Migration;

class m210724_134121_votings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->update('votingBlock', ['votingStatus' => 0]);
        $this->addColumn('votingBlock', 'majorityType', 'TINYINT NULL DEFAULT NULL AFTER title');
        $this->addColumn('votingBlock', 'votesPublic', 'TINYINT NULL DEFAULT NULL AFTER majorityType');
        $this->addColumn('votingBlock', 'resultsPublic', 'TINYINT NULL DEFAULT NULL AFTER votesPublic');
        $this->addColumn('votingBlock', 'assignedToMotionId', 'INT NULL DEFAULT NULL AFTER votesPublic');
        $this->addColumn('votingBlock', 'usersPresentByOrga', 'TEXT NULL DEFAULT NULL AFTER assignedToMotionId');
        $this->addColumn('votingBlock', 'activityLog', 'TEXT NULL DEFAULT NULL AFTER votingStatus');
        $this->alterColumn('votingBlock', 'votingStatus', 'TINYINT NOT NULL');
        $this->addForeignKey('fk_votingblock_assigned_to_motion', 'votingBlock', 'assignedToMotionId', 'motion', 'id');

        $this->createTable('vote', [
            'id' => 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'userId'      => 'INTEGER NOT NULL',
            'votingBlockId' => 'INTEGER NOT NULL',
            'motionId' => 'INTEGER NULL DEFAULT NULL',
            'amendmentId' => 'INTEGER NULL DEFAULT NULL',
            'vote' => 'TINYINT NOT NULL',
            'public' => 'TINYINT NOT NULL',
            'dateVote' => 'TIMESTAMP NOT NULL',
        ]);
        $this->addForeignKey('fk_vote_user', 'vote', 'userId', 'user', 'id');
        $this->addForeignKey('fk_vote_vote', 'vote', 'votingBlockId', 'votingBlock', 'id');
        $this->addForeignKey('fk_vote_motion', 'vote', 'motionId', 'motion', 'id');
        $this->addForeignKey('fk_vote_amendment', 'vote', 'amendmentId', 'amendment', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_vote_user', 'vote');
        $this->dropForeignKey('fk_vote_vote', 'vote');
        $this->dropForeignKey('fk_vote_motion', 'vote');
        $this->dropForeignKey('fk_vote_amendment', 'vote');

        $this->dropTable('vote');

        $this->dropForeignKey('fk_votingblock_assigned_to_motion', 'votingBlock');

        $this->alterColumn('votingBlock', 'votingStatus', 'TINYINT DEFAULT NULL');
        $this->dropColumn('votingBlock', 'activityLog');
        $this->dropColumn('votingBlock', 'assignedToMotionId');
        $this->dropColumn('votingBlock', 'usersPresentByOrga');
        $this->dropColumn('votingBlock', 'votesPublic');
        $this->dropColumn('votingBlock', 'resultsPublic');
        $this->dropColumn('votingBlock', 'majorityType');
        $this->update('votingBlock', ['votingStatus' => 11]);
    }
}
