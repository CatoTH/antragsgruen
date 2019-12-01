<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Class m191201_080255_motion_support_types
 */
class m191201_080255_motion_support_types extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('consultationMotionType', 'contactName');
        $this->dropColumn('consultationMotionType', 'contactEmail');
        $this->dropColumn('consultationMotionType', 'contactPhone');

        $this->addColumn('consultationMotionType', 'supportTypeMotions', 'TEXT NULL DEFAULT NULL AFTER supportType');
        $this->addColumn('consultationMotionType', 'supportTypeAmendments', 'TEXT NULL DEFAULT NULL AFTER supportTypeMotions');

        $connection = \Yii::$app->db;

        // Don't use active records here, as later migrations might add/delete other columns which the
        // source code of the active rectords would already expect here

        $query = new Query();
        $types = $query->select(['id', 'supportType', 'supportTypeSettings'])->from('consultationMotionType')->all();
        foreach ($types as $type) {
            if ($type['supportTypeSettings']) {
                $settings = json_decode($type['supportTypeSettings'], true);
            } else {
                $settings = [];
            }
            $settings['type'] = intval($type['supportType']);

            $connection->createCommand()->update(
                'consultationMotionType',
                ['supportTypeMotions' => json_encode($settings, JSON_PRETTY_PRINT)],
                ['id' => $type['id']]
            )->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m191201_080255_motion_support_types cannot be reverted.\n";

        return false;
    }
}
