<?php

use yii\db\Migration;

class m170206_185458_supporter_contact_name extends Migration
{

    // Use safeUp/safeDown to run migration code within a transaction
    public function up()
    {
        $this->addColumn('motionSupporter', 'contactName', 'TEXT DEFAULT NULL AFTER resolutionDate');
        $this->addColumn('amendmentSupporter', 'contactName', 'TEXT DEFAULT NULL AFTER resolutionDate');
        $this->addColumn('consultationMotionType', 'contactName', 'TINYINT NOT NULL AFTER amendmentLikesDislikes');

        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;

        $table = $params->tablePrefix . 'motionSupporter';
        $sql = "UPDATE `$table` SET `contactName` = `name` WHERE `personType` = 1";
        \Yii::$app->db->createCommand($sql)->execute();

        $table = $params->tablePrefix . 'amendmentSupporter';
        $sql = "UPDATE `$table` SET `contactName` = `name` WHERE `personType` = 1";
        \Yii::$app->db->createCommand($sql)->execute();
    }

    public function down()
    {
        $this->dropColumn('motionSupporter', 'contactName');
        $this->dropColumn('amendmentSupporter', 'contactName');
        $this->dropColumn('consultationMotionType', 'contactName');
    }
}
