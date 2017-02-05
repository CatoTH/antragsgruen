<?php

use yii\db\Migration;

class m170204_191243_additional_user_fields extends Migration
{
    public function safeUp()
    {
        $this->addColumn('user', 'nameGiven', 'text DEFAULT NULL AFTER name');
        $this->addColumn('user', 'nameFamily', 'text DEFAULT NULL AFTER nameGiven');
        $this->addColumn('user', 'organization', 'text DEFAULT NULL AFTER nameFamily');
        $this->addColumn('user', 'fixedData', 'TINYINT DEFAULT 0 AFTER email');
    }

    public function safeDown()
    {
        $this->dropColumn('user', 'nameGiven');
        $this->dropColumn('user', 'nameFamily');
        $this->dropColumn('user', 'organization');
        $this->dropColumn('user', 'fixedData');
    }
}
