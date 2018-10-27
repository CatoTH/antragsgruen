<?php

use yii\db\Migration;

/**
 * Class m181027_174827_consultationFilesSite
 */
class m181027_174827_consultationFilesSite extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('consultationFile', 'siteId', 'INT DEFAULT NULL AFTER consultationId');
        $this->createIndex('consultation_file_site', 'consultationFile', 'siteId', false);
        $this->addForeignKey('fk_consultation_file_site', 'consultationFile', 'siteId', 'site', 'id', 'CASCADE', 'CASCADE');

        $this->alterColumn('consultationFile', 'consultationId', 'INT DEFAULT NULL');

        $this->execute('UPDATE consultationFile a JOIN consultation b ON a.consultationId = b.id SET a.siteId = b.siteId');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_consultation_file_site', 'consultationFile');
        $this->dropColumn('consultationFile', 'siteId');
    }
}
