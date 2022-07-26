<?php

use yii\db\Migration;

class m220710_114056_document_file_groups extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('consultationFileGroup', [
            'id' => 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'consultationId' => 'INTEGER NOT NULL',
            'parentGroupId' => 'INTEGER NULL DEFAULT NULL',
            'position' => 'INTEGER NOT NULL',
            'title' => 'VARCHAR(250) NOT NULL',
        ]);

        $this->addColumn('consultationFile', 'fileGroupId', 'INTEGER NULL DEFAULT NULL AFTER siteId');

        $this->addForeignKey('fk_filegroup_consultation', 'consultationFileGroup', 'consultationId', 'consultation', 'id');
        $this->addForeignKey('fk_filegroup_parent', 'consultationFileGroup', 'parentGroupId', 'consultationFileGroup', 'id');
        $this->addForeignKey('fk_file_group', 'consultationFile', 'fileGroupId', 'consultationFileGroup', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_filegroup_consultation', 'consultationFileGroup');
        $this->dropForeignKey('fk_filegroup_parent', 'consultationFileGroup');
        $this->dropForeignKey('fk_file_group', 'consultationFile');
        $this->dropColumn('consultationFile', 'fileGroupId');
        $this->dropTable('consultationFileGroup');
    }
}
