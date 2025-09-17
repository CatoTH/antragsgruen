<?php

use yii\db\Migration;

class m250916_234203_maxLen_not_nullable extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->update('consultationSettingsMotionSection', ['maxLen' => 0], ['maxLen' => null]);
        $this->alterColumn('consultationSettingsMotionSection', 'maxLen', 'int NOT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->alterColumn('consultationSettingsMotionSection', 'maxLen', 'int NULL');
    }
}
