<?php

use yii\db\Migration;

/**
 * Class m180623_113955_motionTypeSettings
 */
class m180623_113955_motionTypeSettings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationMotionType', 'settings', 'TEXT DEFAULT NULL AFTER position');

        $connection = \Yii::$app->db;

        /** @var \app\models\db\Consultation[] $consultations */
        $consultations = \app\models\db\Consultation::find()->all();
        foreach ($consultations as $consultation) {
            foreach ($consultation->motionTypes as $motionType) {
                $settings                  = new \app\models\settings\MotionType(null);
                $settings->pdfIntroduction = $consultation->getSettings()->pdfIntroduction;
                $settings->cssIcon         = $motionType->cssIcon;
                $settings->layoutTwoCols   = $motionType->layoutTwoCols;

                // Don't use active records here, as later migrations might add/delete other columns which the
                // source code of the active rectords would already expect here
                $connection->createCommand()->update(
                    'consultationMotionType',
                    ['settings' => json_encode($settings, JSON_PRETTY_PRINT)],
                    ['id' => $motionType->id]
                )->execute();
            }
        }

        $this->dropColumn('consultationMotionType', 'cssIcon');
        $this->dropColumn('consultationMotionType', 'layoutTwoCols');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180623_113955_motionTypeSettings cannot be reverted.\n";

        return false;
    }
}
