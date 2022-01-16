<?php

use yii\db\Migration;

class m220116_154835_policy_data extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->alterColumn('consultationMotionType', 'policyMotions', 'TEXT NOT NULL');
        $this->alterColumn('consultationMotionType', 'policyAmendments', 'TEXT NOT NULL');
        $this->alterColumn('consultationMotionType', 'policyComments', 'TEXT NOT NULL');
        $this->alterColumn('consultationMotionType', 'policySupportMotions', 'TEXT NOT NULL');
        $this->alterColumn('consultationMotionType', 'policySupportAmendments', 'TEXT NOT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('consultationMotionType', 'policyMotions', 'INT');
        $this->alterColumn('consultationMotionType', 'policyAmendments', 'INT');
        $this->alterColumn('consultationMotionType', 'policyComments', 'INT');
        $this->alterColumn('consultationMotionType', 'policySupportMotions', 'INT');
        $this->alterColumn('consultationMotionType', 'policySupportAmendments', 'INT');
    }
}
