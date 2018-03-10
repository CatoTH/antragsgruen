<?php

use yii\db\Migration;

class m170730_094020_amendment_proposed_changes extends Migration
{
    public function safeUp()
    {
        $this->addColumn('amendment', 'proposalStatus', 'TINYINT DEFAULT NULL');
        $this->addColumn('amendment', 'proposalReferenceId', 'INTEGER DEFAULT NULL');
        $this->addColumn('amendment', 'proposalComment', 'TEXT NULL DEFAULT NULL');

        $this->createIndex('amendment_reference_am', 'amendment', 'proposalReferenceId', false);
        $this->addForeignKey('fk_amendment_reference_am', 'amendment', 'proposalReferenceId', 'amendment', 'id', 'CASCADE', 'CASCADE');

        $this->addColumn('consultationUserPrivilege', 'adminProposals', 'TINYINT DEFAULT 0 NOT NULL');
    }

    public function safeDown()
    {
        $this->dropColumn('consultationUserPrivilege', 'adminProposals');

        $this->dropForeignKey('fk_amendment_reference_am', 'amendment');
        $this->dropIndex('amendment_reference_am', 'amendment');
        $this->dropColumn('amendment', 'proposalComment');
        $this->dropColumn('amendment', 'proposalReferenceId');
        $this->dropColumn('amendment', 'proposalStatus');
    }
}
