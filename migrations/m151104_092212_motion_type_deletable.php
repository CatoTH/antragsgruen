<?php

use yii\db\Schema;
use yii\db\Migration;

class m151104_092212_motion_type_deletable extends Migration
{
    public function safeUp()
    {
        $this->addColumn('consultationMotionType', 'status', 'smallint default 0');
    }

    public function safeDown()
    {
        $this->dropColumn('consultationMotionType', 'status');
    }
}
