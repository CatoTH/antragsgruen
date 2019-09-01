<?php

use app\models\db\Motion;
use yii\db\Migration;

/**
 * Class m190901_065243_deleteOldMergingDrafts
 */
class m190901_065243_deleteOldMergingDrafts extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->update('motion', ['status' => Motion::STATUS_DELETED], ['status' => Motion::STATUS_MERGING_DRAFT_PUBLIC]);
        $this->update('motion', ['status' => Motion::STATUS_DELETED], ['status' => Motion::STATUS_MERGING_DRAFT_PRIVATE]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190901_065243_deleteOldMergingDrafts cannot be reverted.\n";

        return false;
    }
}
