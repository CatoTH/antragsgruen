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

        $connection = \Yii::$app->db;
        $types      = \app\models\db\ConsultationMotionType::find()->all();
        foreach ($types as $type) {
            $amendments = [];
            $motions    = [];
            if ($type->deadlineAmendments) {
                $amendments[] = [
                    'start' => null,
                    'end'   => $type->deadlineAmendments,
                    'title' => null,
                ];
            }
            if ($type->deadlineMotions) {
                $motions[] = [
                    'start' => null,
                    'end'   => $type->deadlineMotions,
                    'title' => null,
                ];
            }

            // Don't use active records here, as later migrations might add/delete other columns which the source code
            // of the active rectords would already expect here
            $connection->createCommand()->update(
                'consultationMotionType',
                ['deadlines' => json_encode(['amendments' => $amendments, 'motions' => $motions])],
                ['id' => $type->id]
            )->execute();
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
