<?php

use yii\db\Migration;

class m240427_090527_motion_status_index extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createIndex('motion_status_string', 'motion', 'statusString', false);
        $this->createIndex('amendment_status_string', 'amendment', 'statusString', false);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropIndex('motion_status_string', 'motion');
        $this->dropIndex('amendment_status_string', 'amendment');
    }
}
