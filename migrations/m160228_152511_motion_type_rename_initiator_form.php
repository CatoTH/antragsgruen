<?php

use yii\db\Migration;

class m160228_152511_motion_type_rename_initiator_form extends Migration
{
    /**
     */
    public function safeUp()
    {
        $this->renameColumn('consultationMotionType', 'initiatorForm', 'supportType');
        $this->renameColumn('consultationMotionType', 'initiatorFormSettings', 'supportTypeSettings');
    }

    /**
     */
    public function safeDown()
    {
        $this->renameColumn('consultationMotionType', 'supportType', 'initiatorForm');
        $this->renameColumn('consultationMotionType', 'supportTypeSettings', 'initiatorFormSettings');
    }
}
