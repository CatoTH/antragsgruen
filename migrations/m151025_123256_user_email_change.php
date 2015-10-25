<?php

use yii\db\Schema;
use yii\db\Migration;

class m151025_123256_user_email_change extends Migration
{
    /*
    public function up()
    {

    }

    public function down()
    {
    }
    */

    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->addColumn('user', 'emailChange', 'string null');
        $this->addColumn('user', 'emailChangeAt', 'timestamp null');
    }

    public function safeDown()
    {
        $this->dropColumn('user', 'emailChange');
        $this->dropColumn('user', 'emailChangeAt');
    }
}
