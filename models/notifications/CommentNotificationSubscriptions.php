<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\EMailLog;
use app\models\db\IComment;
use app\models\db\User;

class CommentNotificationSubscriptions extends Base implements IEmailUser
{
    /** @var User */
    private $user;

    /** @var IComment */
    private $comment;

    /**
     * CommentNotification constructor.
     * @param User $user
     * @param IComment $comment
     */
    public function __construct(User $user, IComment $comment)
    {
        $this->user         = $user;
        $this->comment      = $comment;
        $this->consultation = $comment->getConsultation();

        parent::__construct();
    }

    /**
     * @return User
     */
    public function getEmailUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getEmailUserText()
    {
        return str_replace(
            ['%TITLE%', '%LINK%'],
            [$this->comment->getMotionTitle(), UrlHelper::absolutizeLink($this->comment->getLink())],
            \Yii::t('user', 'noti_new_comment_body')
        );
    }

    /**
     * @return string
     */
    public function getEmailUserSubject()
    {
        $motionTitle = $this->comment->getMotionTitle();
        return str_replace('%TITLE%', $motionTitle, \Yii::t('user', 'noti_new_comment_title'));
    }

    /**
     * @return int
     */
    public function getEmailUserType()
    {
        return EMailLog::TYPE_COMMENT_NOTIFICATION_USER;
    }
}
