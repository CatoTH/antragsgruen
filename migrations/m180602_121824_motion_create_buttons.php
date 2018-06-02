<?php

use yii\db\Migration;

/**
 * Class m180602_121824_motion_create_buttons
 */
class m180602_121824_motion_create_buttons extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationMotionType', 'sidebarCreateButton', 'TINYINT NOT NULL DEFAULT 1 AFTER createTitle');

        \Yii::$app->db->schema->getTableSchema('consultationMotionType', true);
        /** @var \app\models\db\Consultation[] $consultations */
        $consultations = \app\models\db\Consultation::find()->all();
        foreach ($consultations as $consultation) {
            if (count($consultation->motionTypes) > 1) {
                foreach ($consultation->motionTypes as $motionType) {
                    $motionType->setAttribute('sidebarCreateButton', 0);
                    $motionType->save();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('consultationMotionType', 'sidebarCreateButton');
    }
}
