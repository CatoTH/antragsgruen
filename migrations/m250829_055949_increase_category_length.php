<?php

use yii\db\Migration;

class m250829_055949_increase_category_length extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->alterColumn('consultationText', 'category', 'VARCHAR(128) NOT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->alterColumn('consultationText', 'category', 'VARCHAR(20) NOT NULL');
    }
}
