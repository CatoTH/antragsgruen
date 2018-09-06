<?php

use yii\db\Migration;

/**
 * Class m180902_182805_initiatorSettings
 */
class m180902_182805_initiatorSettings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $types = \app\models\db\ConsultationMotionType::find()->all();
        foreach ($types as $type) {
            $settings                    = $type->getMotionSupportTypeClass()->getSettingsObj();
            $settings->contactEmail      = IntVal($type->contactEmail);
            $settings->contactPhone      = IntVal($type->contactPhone);
            $settings->contactName       = IntVal($type->contactName);
            $settings->hasResolutionDate = false;
            $type->supportTypeSettings   = json_encode($type, JSON_PRETTY_PRINT);
            $type->save();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180902_182805_initiatorSettings cannot be reverted.\n";

        return false;
    }
}
