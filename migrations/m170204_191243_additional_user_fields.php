<?php

use yii\db\Migration;

class m170204_191243_additional_user_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('user', 'nameGiven', 'text AFTER name');
        $this->addColumn('user', 'nameFamily', 'text AFTER nameGiven');
        $this->addColumn('user', 'organisation', 'text AFTER nameFamily');
    }

    public function safeDown()
    {
        $this->dropColumn('user', 'nameGiven');
        $this->dropColumn('user', 'nameFamily');
        $this->dropColumn('user', 'organisation');
    }
}
