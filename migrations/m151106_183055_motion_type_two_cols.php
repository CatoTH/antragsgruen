<?php

use yii\db\Schema;
use yii\db\Migration;

class m151106_183055_motion_type_two_cols extends Migration
{
    public function safeUp()
    {
        $this->addColumn('consultationMotionType', 'layoutTwoCols', 'smallint default 0');
    }

    public function safeDown()
    {
        $this->dropColumn('consultationMotionType', 'layoutTwoCols');
    }
}
