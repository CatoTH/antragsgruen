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
        $this->addColumn('motionSupporter', 'extraData', 'TEXT DEFAULT NULL');
        $this->addColumn('amendmentSupporter', 'extraData', 'TEXT DEFAULT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('motionSupporter', 'extraData');
        $this->dropColumn('amendmentSupporter', 'extraData');
    }
}
