<?php

use yii\db\Migration;

/**
 * Handles adding amendAmendments to table `consultationMotionType`.
 */
class m190610_044858_add_amendAmendments_column_to_consultationMotionType_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationMotionType', 'amendAmendments', $this->boolean());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('consultationMotionType', 'amendAmendments');
    }
}
