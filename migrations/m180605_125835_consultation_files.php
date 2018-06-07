<?php

use yii\db\Migration;

/**
 * Class m180605_125835_consultation_files
 */
class m180605_125835_consultation_files extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('consultationFile', [
            'id'             => 'INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'consultationId' => 'INTEGER NOT NULL',
            'filename'       => 'VARCHAR(250) NOT NULL',
            'filesize'       => 'INTEGER NOT NULL',
            'mimetype'       => 'VARCHAR(250) NOT NULL',
            'width'          => 'INTEGER DEFAULT NULL',
            'height'         => 'INTEGER DEFAULT NULL',
            'data'           => 'MEDIUMBLOB NOT NULL',
            'dataHash'       => 'VARCHAR(40) NOT NULL',
            'dateCreation'   => 'TIMESTAMP NOT NULL',
        ]);
        $this->addForeignKey('fk_file_consultation', 'consultationFile', 'consultationId', 'consultation', 'id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_file_consultation', 'consultationFile');
        $this->dropTable('consultationFile');
    }
}
