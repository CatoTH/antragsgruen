<?php

use yii\db\Migration;

class m241013_105549_pages_files extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationFileGroup', 'consultationTextId', 'INT(11) NULL DEFAULT NULL AFTER `consultationId`');
        $this->addForeignKey('file_groups_fk_texts', 'consultationFileGroup', 'consultationTextId', 'consultationText', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('file_groups_fk_texts', 'consultationFileGroup');
        $this->dropColumn('consultationFileGroup', 'consultationTextId');
    }
}
