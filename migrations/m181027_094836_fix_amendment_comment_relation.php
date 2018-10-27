<?php

use yii\db\Migration;

/**
 * Class m181027_094836_fix_amendment_comment_relation
 */
class m181027_094836_fix_amendment_comment_relation extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Rebuilds the amendmentComment field. Bugfix for those cases where the bugged init data was used
        // that didn't work on case-sensitive database installations
        $this->dropForeignKey('fk_amendment_comment_parents', 'amendmentComment');
        $this->addForeignKey('fk_amendment_comment_parents', 'amendmentComment', 'parentCommentId', 'amendmentComment', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
    }
}
