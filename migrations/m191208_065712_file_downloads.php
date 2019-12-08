<?php

use yii\db\Migration;

/**
 * Class m191208_065712_file_downloads
 */
class m191208_065712_file_downloads extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationFile', 'downloadPosition', 'MEDIUMINT NULL DEFAULT NULL AFTER siteId');
        $this->addColumn('consultationFile', 'title', 'TEXT DEFAULT NULL AFTER filename');
        $this->addColumn('consultationFile', 'uploadedById', 'INT NULL DEFAULT NULL');
        $this->alterColumn('consultationFile', 'dateCreation', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addForeignKey('fk_file_uploaded_by', 'consultationFile', 'uploadedById', 'user', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_file_uploaded_by', 'consultationFile');
        $this->dropColumn('consultationFile', 'title');
        $this->dropColumn('consultationFile', 'uploadedById');
        $this->dropColumn('consultationFile', 'downloadPosition');
    }
}
