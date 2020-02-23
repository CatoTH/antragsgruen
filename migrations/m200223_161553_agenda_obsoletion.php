<?php

use yii\db\Migration;

class m200223_161553_agenda_obsoletion extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('consultationAgendaItem', 'deadline');
        $this->dropColumn('consultationAgendaItem', 'description');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m200223_161553_agenda_obsoletion cannot be reverted.\n";

        return false;
    }
}
