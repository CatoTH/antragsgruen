<?php

use yii\db\Migration;

/**
 * Class m181101_161124_proposed_procedure_active
 */
class m181101_161124_proposed_procedure_active extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        /** @var \app\models\db\ConsultationMotionType[] $types */
        $types = \app\models\db\ConsultationMotionType::find()->all();
        foreach ($types as $type) {
            $hasProposedProcedure = false;
            foreach ($type->motions as $motion) {
                if ($motion->proposalStatus !== null) {
                    $hasProposedProcedure = true;
                }
                foreach ($motion->amendments as $amendment) {
                    if ($amendment->proposalStatus !== null) {
                        $hasProposedProcedure = true;
                    }
                }
            }
            if ($hasProposedProcedure) {
                $settings                       = $type->getSettingsObj();
                $settings->hasProposedProcedure = true;
                $type->setSettingsObj($settings);
                $type->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
