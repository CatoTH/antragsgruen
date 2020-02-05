<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\Motion;

class MotionEdited extends Base implements IEmailAdmin
{
    protected $motion;

    /**
     * MotionInitiallySubmitted constructor.
     * @param Motion $motion
     */
    public function __construct(Motion $motion)
    {
        $this->motion       = $motion;
        $this->consultation = $motion->getMyConsultation();

        parent::__construct();
    }

    public function getEmailAdminText(): string
    {
        $mailText = \Yii::t('motion', 'edit_mail_body');
        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this->motion));
        return str_replace(['%TITLE%', '%LINK%'], [$this->motion->getTitleWithIntro(), $motionLink], $mailText);
    }

    public function getEmailAdminSubject(): string
    {
        return \Yii::t('motion', 'edit_mail_title');
    }
}
