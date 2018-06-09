<?php

use yii\db\Migration;

/**
 * Class m180609_095225_consultation_text_in_menu
 */
class m180609_095225_consultation_text_in_menu extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationText', 'menuPosition', 'INT DEFAULT NULL AFTER textId');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('consultationText', 'menuPosition');
    }
}
