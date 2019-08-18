<?php

use yii\db\Migration;

/**
 * Class m190816_074556_votingData
 */
class m190816_074556_votingData extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('motion', 'votingData', 'TEXT NULL DEFAULT NULL');
        $this->addColumn('amendment', 'votingData', 'TEXT NULL DEFAULT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('motion', 'votingData');
        $this->dropColumn('amendment', 'votingData');
    }
}
