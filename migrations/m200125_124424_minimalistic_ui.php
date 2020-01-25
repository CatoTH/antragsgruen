<?php

use yii\db\Migration;

/**
 * Class m200125_124424_minimalistic_ui
 */
class m200125_124424_minimalistic_ui extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /** @var \app\models\db\Consultation[] $consultations */
        $consultations = \app\models\db\Consultation::find()->all();
        foreach ($consultations as $consultation) {
            $settings = $consultation->getSettings();
            if ($settings->minimalisticUI) {
                $settings->motiondataMode = \app\models\settings\Consultation::MOTIONDATA_NONE;
                $consultation->setSettings($settings);
                $consultation->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        return true;
    }
}
