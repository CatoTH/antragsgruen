<?php

declare(strict_types=1);

use yii\db\Migration;

class m260810_120000_speech_queue_amendment extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->addColumn('speechQueue', 'amendmentId', 'INTEGER DEFAULT NULL AFTER motionId');
        $this->addForeignKey('fk_speech_amendment', 'speechQueue', 'amendmentId', 'amendment', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropForeignKey('fk_speech_amendment', 'speechQueue');
        $this->dropColumn('speechQueue', 'amendmentId');
    }
}
