<?php

use yii\db\Migration;

/**
 * Class m191101_162351_motion_responsibility
 */
class m191101_162351_motion_responsibility extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('motion', 'responsibilityId', 'INT DEFAULT NULL');
        $this->addColumn('motion', 'responsibilityComment', 'TEXT DEFAULT NULL');
        $this->addForeignKey('fk_motion_responsibility', 'motion', 'responsibilityId', 'user', 'id');
        $this->addColumn('amendment', 'responsibilityId', 'INT DEFAULT NULL');
        $this->addColumn('amendment', 'responsibilityComment', 'TEXT DEFAULT NULL');
        $this->addForeignKey('fk_amendment_responsibility', 'amendment', 'responsibilityId', 'user', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_amendment_responsibility', 'amendment');
        $this->dropColumn('amendment', 'responsibilityId');
        $this->dropColumn('amendment', 'responsibilityComment');
        $this->dropForeignKey('fk_motion_responsibility', 'motion');
        $this->dropColumn('motion', 'responsibilityId');
        $this->dropColumn('motion', 'responsibilityComment');
    }
}
