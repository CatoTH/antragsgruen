<?php

use yii\db\Migration;

class m210404_173210_statute_amendments extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationMotionType', 'amendmentsOnly', 'tinyint NOT NULL default 0 AFTER settings');

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
        $this->dropColumn('consultationMotionType', 'amendmentsOnly');
    }
}
