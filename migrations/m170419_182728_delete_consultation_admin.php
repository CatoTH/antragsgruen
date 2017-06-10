<?php

use yii\db\Migration;

class m170419_182728_delete_consultation_admin extends Migration
{
    public function up()
    {
        $this->dropTable('consultationAdmin');
    }

    public function down()
    {
        echo "m170419_182728_delete_consultation_admin cannot be reverted.\n";

        return false;
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
