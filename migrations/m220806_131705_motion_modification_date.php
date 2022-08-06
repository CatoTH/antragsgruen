<?php

use yii\db\Migration;

class m220806_131705_motion_modification_date extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('motion', 'dateContentModification', 'TIMESTAMP NOT NULL AFTER dateResolution');
        $this->addColumn('amendment', 'dateContentModification', 'TIMESTAMP NOT NULL AFTER dateResolution');
        $this->execute('UPDATE motion SET dateContentModification = dateCreation');
        $this->execute('UPDATE amendment SET dateContentModification = dateCreation');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('motion', 'dateContentModification');
        $this->dropColumn('amendment', 'dateContentModification');
    }
}
