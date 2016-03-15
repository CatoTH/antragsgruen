<?php

use yii\db\Migration;

class m160304_095858_motion_slug extends Migration
{
    public function safeUp()
    {
        $this->addColumn('motion', 'slug', 'varchar(100)');
        $this->createIndex('motionSlug', 'motion', ['consultationId', 'slug'], true);
    }

    public function safeDown()
    {
        $this->dropIndex('motionSlug', 'motion');
        $this->dropColumn('motion', 'slug');
    }
}
