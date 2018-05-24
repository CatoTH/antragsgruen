<?php

use yii\db\Migration;

/**
 * Class m180524_153540_motionTypeDeadlines
 */
class m180524_153540_motionTypeDeadlines extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationMotionType', 'deadlines', 'TEXT DEFAULT NULL AFTER deadlineAmendments');

        \Yii::$app->db->schema->getTableSchema('consultationMotionType', true);

        $types = \app\models\db\ConsultationMotionType::find()->all();
        foreach ($types as $type) {
            $amendments = [];
            $motions    = [];
            if ($type->deadlineAmendments) {
                $amendments[] = [
                    'start' => null,
                    'end'   => $type->deadlineAmendments,
                ];
            }
            if ($type->deadlineMotions) {
                $motions[] = [
                    'start' => null,
                    'end'   => $type->deadlineMotions,
                ];
            }
            $type->setAttribute("deadlines", json_encode([
                'amendments' => $amendments,
                'motions'    => $motions,
            ]));
            $type->save();
        }

        $this->dropColumn('consultationMotionType', 'deadlineAmendments');
        $this->dropColumn('consultationMotionType', 'deadlineMotions');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m180524_153540_motionTypeDeadlines cannot be reverted.\n";

        return false;
    }
}
