<?php

use yii\db\Migration;

/**
 * Handles adding amendAmendments to table `consultationMotionType`.
 */
class m190610_044858_amendAmendments extends Migration
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
