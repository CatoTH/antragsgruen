<?php

use yii\db\Migration;

class m230318_132711_hierarchical_tags_with_settings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('consultationSettingsTag', 'cssicon');
        $this->addColumn('consultationSettingsTag', 'parentTagId', 'INT(11) NULL DEFAULT NULL AFTER `consultationId`');
        $this->addColumn('consultationSettingsTag', 'settings', 'TEXT NULL DEFAULT NULL');

        $this->addForeignKey('tags_fk_tags', 'consultationSettingsTag', 'parentTagId', 'consultationSettingsTag', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('tags_fk_tags', 'consultationSettingsTag');

        $this->dropColumn('consultationSettingsTag', 'settings');
        $this->dropColumn('consultationSettingsTag', 'parentTagId');
        $this->addColumn('consultationSettingsTag', 'cssicon', 'SMALLINT(6) DEFAULT 0');
    }
}
