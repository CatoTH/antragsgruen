<?php

use yii\db\Migration;

/**
 * Class m180531_062049_parent_motion_ids
 */
class m180531_062049_parent_motion_ids extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('motionComment', 'parentCommentId', 'INT DEFAULT NULL AFTER motionId');
        $this->addColumn('amendmentComment', 'parentCommentId', 'INT DEFAULT NULL AFTER amendmentId');

        $this->addForeignKey('fk_motion_comment_parents', 'motionComment', 'parentCommentId', 'motionComment', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_amendment_comment_parents', 'amendmentComment', 'parentCommentId', 'amendmentComment', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_amendment_comment_parents', 'amendmentComment');
        $this->dropForeignKey('fk_motion_comment_parents', 'motionComment');

        $this->dropColumn('amendmentComment', 'parentCommentId');
        $this->dropColumn('motionComment', 'parentCommentId');
    }
}
