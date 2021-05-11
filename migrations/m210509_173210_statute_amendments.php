<?php

use yii\db\Migration;

class m210509_173210_statute_amendments extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationMotionType', 'amendmentsOnly', 'tinyint NOT NULL default 0 AFTER settings');
        $this->addColumn('amendment', 'agendaItemId', 'int NULL DEfAULT NULL AFTER motionId');
        $this->addForeignKey('fk_amendment_agenda', 'amendment', 'agendaItemId', 'consultationAgendaItem', 'id');

        $table = Yii::$app->db->schema->getTableSchema('consultationMotionType');
        if (isset($table->columns['cssIcon'])) {
            $this->dropColumn('consultationMotionType', 'cssIcon');
        } else {
            echo "Skipped dropping column cssIcon, as it did not exist\n";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_amendment_agenda', 'amendment');
        $this->dropColumn('amendment', 'agendaItemId');
        $this->dropColumn('consultationMotionType', 'amendmentsOnly');
    }
}
