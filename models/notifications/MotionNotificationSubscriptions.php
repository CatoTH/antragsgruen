<?php

namespace app\models\notifications;

use app\models\db\{EMailLog, Motion, User};

class MotionNotificationSubscriptions extends Base implements IEmailUser
{
    public function __construct(
        private Motion $motion,
        private User $user
    ) {
        $this->consultation = $motion->getMyConsultation();

        parent::__construct();
    }

    public function getEmailUser(): User
    {
        return $this->user;
    }

    public function getEmailUserSubject(): string
    {
        return \Yii::t('user', 'noti_new_motion_title') . ' ' . $this->motion->getTitleWithPrefix();
    }

    public function getEmailUserText(): string
    {
        $link      = $this->motion->getLink(true);
        $initiator = $this->motion->getInitiatorsStr();
        return str_replace(
            ['%CONSULTATION%', '%TITLE%', '%LINK%', '%INITIATOR%'],
            [$this->consultation->title, $this->motion->getTitleWithPrefix(), $link, $initiator],
            \Yii::t('user', 'noti_new_motion_body')
        );
    }

    public function getEmailUserType(): int
    {
        return EMailLog::TYPE_MOTION_NOTIFICATION_USER;
    }
}
