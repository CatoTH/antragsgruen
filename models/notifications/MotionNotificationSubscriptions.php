<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\EMailLog;
use app\models\db\Motion;
use app\models\db\User;

class MotionNotificationSubscriptions extends Base implements IEmailUser
{
    /** @var Motion */
    private $motion;

    /** @var User */
    private $user;

    /**
     * MotionNotification constructor.
     * @param Motion $motion
     * @param User $user
     */
    public function __construct(Motion $motion, User $user)
    {
        $this->motion       = $motion;
        $this->user         = $user;
        $this->consultation = $motion->getMyConsultation();

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
    public function getEmailUserSubject()
    {
        return \Yii::t('user', 'noti_new_motion_title') . ' ' . $this->motion->getTitleWithPrefix();
    }

    /**
     * @return string
     */
    public function getEmailUserText()
    {
        $link      = $this->motion->getLink(true);
        $initiator = $this->motion->getInitiatorsStr();
        return str_replace(
            ['%CONSULTATION%', '%TITLE%', '%LINK%', '%INITIATOR%'],
            [$this->consultation->title, $this->motion->getTitleWithPrefix(), $link, $initiator],
            \Yii::t('user', 'noti_new_motion_body')
        );
    }

    /**
     * @return int
     */
    public function getEmailUserType()
    {
        return EMailLog::TYPE_MOTION_NOTIFICATION_USER;
    }
}
