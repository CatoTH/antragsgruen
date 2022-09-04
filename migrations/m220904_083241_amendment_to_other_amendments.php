<?php

use yii\db\Migration;

class m220904_083241_amendment_to_other_amendments extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('amendment', 'amendingAmendmentId', 'int NULL DEfAULT NULL AFTER agendaItemId');
        $this->addForeignKey('fk_amendment_amending', 'amendment', 'amendingAmendmentId', 'amendment', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_amendment_amending', 'amendment');
        $this->dropColumn('amendment', 'amendingAmendmentId');
    }
}
