<?php

use yii\db\Schema;
use yii\db\Migration;

class m160114_200337_motion_section_is_right extends Migration
{
    public function safeUp()
    {
        $this->addColumn('consultationSettingsMotionSection', 'positionRight', 'smallint default 0');
    }

    public function safeDown()
    {
        $this->dropColumn('consultationSettingsMotionSection', 'positionRight');
    }
}
