<?php

use yii\db\Migration;

/**
 * Class m200130_100306_agenda_extension
 */
class m200130_100306_agenda_extension extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationAgendaItem', 'time', 'VARCHAR(20) NULL DEFAULT NULL AFTER position');
        $this->addColumn('consultationAgendaItem', 'settings', 'TEXT NULL DEFAULT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('consultationAgendaItem', 'time');
        $this->dropColumn('consultationAgendaItem', 'settings');
    }
}
