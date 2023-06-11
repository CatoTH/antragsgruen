<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\{EMailLog, IComment, User};

class CommentNotificationSubscriptions extends Base implements IEmailUser
{
    public function __construct(
        private User $user,
        private IComment $comment
    ) {
        $this->consultation = $comment->getConsultation();

        parent::__construct();
    }

    public function getEmailUser(): User
    {
        return $this->user;
    }

    public function getEmailUserText(): string
    {
        return str_replace(
            ['%TITLE%', '%LINK%'],
            [$this->comment->getMotionTitle(), UrlHelper::absolutizeLink($this->comment->getLink())],
            \Yii::t('user', 'noti_new_comment_body')
        );
    }

    public function getEmailUserSubject(): string
    {
        $motionTitle = $this->comment->getMotionTitle();
        return str_replace('%TITLE%', $motionTitle, \Yii::t('user', 'noti_new_comment_title'));
    }

    public function getEmailUserType(): int
    {
        return EMailLog::TYPE_COMMENT_NOTIFICATION_USER;
    }
}
