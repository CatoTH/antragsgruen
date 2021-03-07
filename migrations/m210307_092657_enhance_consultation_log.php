<?php

use yii\db\Migration;

/**
 * Class m210307_092657_enhance_consultation_log
 */
class m210307_092657_enhance_consultation_log extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createIndex('actionReferenceId', 'consultationLog', ['actionReferenceId', 'actionTime']);
        $this->addColumn('consultationLog', 'data', 'TEXT DEFAULT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('actionReferenceId', 'consultationLog');
        $this->dropColumn('consultationLog', 'data');
    }
}
