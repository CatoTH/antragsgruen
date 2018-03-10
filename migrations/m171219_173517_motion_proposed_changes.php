<?php

use yii\db\Migration;

/**
 * Class m171219_173517_motion_proposed_changes
 */
class m171219_173517_motion_proposed_changes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('motion', 'proposalStatus', 'TINYINT DEFAULT NULL');
        $this->addColumn('motion', 'proposalReferenceId', 'INTEGER DEFAULT NULL');
        $this->addColumn('motion', 'proposalComment', 'TEXT NULL DEFAULT NULL');
        $this->addColumn('motion', 'proposalVisibleFrom', 'TIMESTAMP NULL DEFAULT NULL');
        $this->addColumn('motion', 'proposalNotification', 'TIMESTAMP NULL DEFAULT NULL');
        $this->addColumn('motion', 'proposalUserStatus', 'TINYINT NULL DEFAULT NULL');
        $this->addColumn('motion', 'proposalExplanation', 'TEXT NULL DEFAULT NULL');

        $this->createIndex('motion_reference_am', 'motion', 'proposalReferenceId', false);
        $this->addForeignKey('fk_motion_reference_am', 'motion', 'proposalReferenceId', 'motion', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_motion_reference_am', 'motion');
        $this->dropIndex('motion_reference_am', 'motion');
        $this->dropColumn('motion', 'proposalComment');
        $this->dropColumn('motion', 'proposalVisibleFrom');
        $this->dropColumn('motion', 'proposalReferenceId');
        $this->dropColumn('motion', 'proposalStatus');
        $this->dropColumn('motion', 'proposalNotification');
        $this->dropColumn('motion', 'proposalUserStatus');
        $this->dropColumn('motion', 'proposalExplanation');
    }
}
