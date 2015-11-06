<?php

use yii\db\Schema;
use yii\db\Migration;

class m151025_123256_user_email_change extends Migration
{
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
