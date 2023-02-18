<?php

use yii\db\Migration;

class m230218_110905_motion_proposal_reference extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropForeignKey('fk_motion_reference_am', 'motion');
        $this->addForeignKey('fk_motion_reference_am', 'motion', 'proposalReferenceId', 'amendment', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_motion_reference_am', 'motion');
        $this->addForeignKey('fk_motion_reference_am', 'motion', 'proposalReferenceId', 'motion', 'id', 'CASCADE', 'CASCADE');
    }
}
