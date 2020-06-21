<?php

use yii\db\Migration;

class m200621_063838_amendmentMotionExtraData extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('motion', 'extraData', 'TEXT DEFAULT NULL');
        $this->addColumn('amendment', 'extraData', 'TEXT DEFAULT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('motion', 'extraData');
        $this->dropColumn('amendment', 'extraData');
    }
}
