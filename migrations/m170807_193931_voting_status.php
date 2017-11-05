<?php

use yii\db\Migration;

class m170807_193931_voting_status extends Migration
{
    public function safeUp()
    {
        $this->createTable('votingBlock', [
            'id'             => 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'consultationId' => 'INTEGER NOT NULL',
            'title'          => 'VARCHAR(150) NOT NULL',
            'votingStatus'   => 'TINYINT DEFAULT NULL',
        ]);
        $this->addForeignKey('fk_voting_block_consultation', 'votingBlock', 'consultationId', 'consultation', 'id');

        $this->addColumn('motion', 'votingStatus', 'TINYINT DEFAULT NULL');
        $this->addColumn('amendment', 'votingStatus', 'TINYINT DEFAULT NULL');
        $this->addColumn('motion', 'votingBlockId', 'INTEGER DEFAULT NULL');
        $this->addColumn('amendment', 'votingBlockId', 'INTEGER DEFAULT NULL');

        $this->createIndex('ix_motion_voting_block', 'motion', 'votingBlockId', false);
        $this->addForeignKey('fk_motion_voting_block', 'motion', 'votingBlockId', 'votingBlock', 'id', 'CASCADE', 'CASCADE');
        $this->createIndex('ix_amendment_voting_block', 'amendment', 'votingBlockId', false);
        $this->addForeignKey('fk_amendment_voting_block', 'amendment', 'votingBlockId', 'votingBlock', 'id', 'CASCADE', 'CASCADE');

        $this->addColumn('amendment', 'proposalVisibleFrom', 'TIMESTAMP NULL DEFAULT NULL');
    }

    public function safeDown()
    {
        $this->dropColumn('amendment', 'proposalVisibleFrom');

        $this->dropForeignKey('fk_amendment_voting_block', 'amendment');
        $this->dropIndex('ix_amendment_voting_block', 'amendment');
        $this->dropForeignKey('fk_motion_voting_block', 'motion');
        $this->dropIndex('ix_motion_voting_block', 'motion');

        $this->dropColumn('amendment', 'votingStatus');
        $this->dropColumn('motion', 'votingStatus');
        $this->dropColumn('amendment', 'votingBlockId');
        $this->dropColumn('motion', 'votingBlockId');

        $this->dropForeignKey('fk_voting_block_consultation', 'votingBlock');
        $this->dropTable('votingBlock');
    }
}
