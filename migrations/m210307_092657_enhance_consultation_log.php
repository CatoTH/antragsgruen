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

        // Unfortunately, this is not completely safe, as for consultations that have both a motion and an amendment of the same ID, we cannot really distinguish it
        $this->execute('UPDATE consultationLog log JOIN amendment amend ON log.actionType = 24 AND log.actionReferenceId = amend.id JOIN motion mot ON amend.motionId = mot.id AND mot.consultationId = log.consultationId SET log.actionType = 33');
        $this->execute('UPDATE consultationLog log JOIN amendment amend ON log.actionType = 26 AND log.actionReferenceId = amend.id JOIN motion mot ON amend.motionId = mot.id AND mot.consultationId = log.consultationId SET log.actionType = 34');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->execute('UPDATE consultationLog SET actionType = 24 WHERE actionType = 33');
        $this->execute('UPDATE consultationLog SET actionType = 26 WHERE actionType = 34');

        $this->dropIndex('actionReferenceId', 'consultationLog');
        $this->dropColumn('consultationLog', 'data');
    }
}
