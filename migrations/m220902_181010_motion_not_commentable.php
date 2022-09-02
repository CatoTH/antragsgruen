<?php

use yii\db\Migration;

class m220902_181010_motion_not_commentable extends Migration
{
    public function up(): void
    {
        $this->addColumn('motion', 'notCommentable', 'TINYINT DEFAULT 0 AFTER nonAmendable');
        $this->addColumn('amendment', 'notCommentable', 'TINYINT DEFAULT 0 AFTER statusString');
    }

    public function down(): void
    {
        $this->dropColumn('motion', 'notCommentable');
        $this->dropColumn('amendment', 'notCommentable');
    }
}
