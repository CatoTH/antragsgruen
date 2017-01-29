<?php

use yii\db\Migration;

class m170129_173812_typo_maintenance extends Migration
{
    public function up()
    {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $table = $params->tablePrefix . 'consultation';
        $sql = "UPDATE `$table` SET `settings` = REPLACE(`settings`, 'maintainanceMode', 'maintenanceMode')";
        \Yii::$app->db->createCommand($sql)->execute();
    }

    public function down()
    {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $table = $params->tablePrefix . 'consultation';
        $sql = "UPDATE `$table` SET `settings` = REPLACE(`settings`, 'maintenanceMode', 'maintainanceMode')";
        \Yii::$app->db->createCommand($sql)->execute();
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
