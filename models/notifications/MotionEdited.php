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

    /**
     * @return string
     */
    public function getEmailAdminText()
    {
        $mailText = \Yii::t('motion', 'edit_mail_body');
        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this->motion));
        return str_replace(['%TITLE%', '%LINK%'], [$this->motion->title, $motionLink], $mailText);
    }

    /**
     * @return string
     */
    public function getEmailAdminTitle()
    {
        return \Yii::t('motion', 'edit_mail_title');
    }
}
