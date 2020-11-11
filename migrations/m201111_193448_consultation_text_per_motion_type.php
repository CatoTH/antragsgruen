<?php

use yii\db\Migration;

/**
 * Class m201111_193448_consultation_text_per_motion_type
 */
class m201111_193448_consultation_text_per_motion_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationText', 'motionTypeId', 'INT DEFAULT NULL AFTER id');
        $this->addForeignKey('fk_text_motion_type', 'consultationText', 'motionTypeId', 'consultationMotionType', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_text_motion_type', 'consultationText');
        $this->dropColumn('consultationText', 'motionTypeId');
    }
}
