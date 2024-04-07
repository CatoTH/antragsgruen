<?php

use yii\db\Migration;

class m240406_155022_vote_weight extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('vote', 'weight', 'INT(11) NOT NULL DEFAULT 1 AFTER `vote`');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('vote', 'weight');
    }
}
