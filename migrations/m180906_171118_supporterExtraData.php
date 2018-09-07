<?php

use yii\db\Migration;

/**
 * Class m180906_171118_supporterExtraData
 */
class m180906_171118_supporterExtraData extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('motionSupporter', 'dateCreation', 'TIMESTAMP NOT NULL');
        $this->addColumn('motionSupporter', 'extraData', 'TEXT DEFAULT NULL');
        $this->addColumn('amendmentSupporter', 'dateCreation', 'TIMESTAMP NOT NULL');
        $this->addColumn('amendmentSupporter', 'extraData', 'TEXT DEFAULT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('motionSupporter', 'extraData');
        $this->dropColumn('motionSupporter', 'dateCreation');
        $this->dropColumn('amendmentSupporter', 'extraData');
        $this->dropColumn('motionSupporter', 'dateCreation');
    }
}
