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
        $connection = \Yii::$app->db;

        /** @var \app\models\db\Consultation[] $consultations */
        $consultations = \app\models\db\Consultation::find()->all();
        foreach ($consultations as $consultation) {
            if (count($consultation->motionTypes) > 1) {
                foreach ($consultation->motionTypes as $motionType) {
                    // Don't use active records here, as later migrations might add/delete other columns which
                    // the source code of the active rectords would already expect here
                    $connection->createCommand()->update(
                        'consultationMotionType',
                        ['sidebarCreateButton' => 0],
                        ['id' => $motionType->id]
                    );
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
