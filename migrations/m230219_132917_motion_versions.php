<?php

use yii\db\Migration;

class m230219_132917_motion_versions extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('motion', 'version', 'VARCHAR(50) NOT NULL DEFAULT \'1\' AFTER titlePrefix');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('motion', 'version');
    }
}
