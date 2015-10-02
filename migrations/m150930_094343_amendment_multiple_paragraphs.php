<?php

use yii\db\Migration;

class m150930_094343_amendment_multiple_paragraphs extends Migration
{
    public function safeUp()
    {
        $this->addColumn('consultationMotionType', 'amendmentMultipleParagraphs', 'boolean');
        $this->update('consultationMotionType', ['amendmentMultipleParagraphs' => 1]);
    }

    public function safeDown()
    {
        $this->dropColumn('consultationMotionType', 'amendmentMultipleParagraphs');
    }
}
