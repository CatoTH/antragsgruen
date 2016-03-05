<?php

use yii\db\Migration;
use yii\db\Expression;

class m160305_201135_support_separate_to_motions_and_amendments extends Migration
{
    /**
     */
    public function safeUp()
    {
        $this->renameColumn('consultationMotionType', 'policySupport', 'policySupportMotions');
        $this->addColumn('consultationMotionType', 'policySupportAmendments', 'int');
        $this->update('consultationMotionType', ['policySupportAmendments' => new Expression('policySupportMotions')]);
    }

    /**
     */
    public function safeDown()
    {
        $this->renameColumn('consultationMotionType', 'policySupportMotions', 'policySupport');
        $this->dropColumn('consultationMotionType', 'policySupportAmendments');
    }
}
