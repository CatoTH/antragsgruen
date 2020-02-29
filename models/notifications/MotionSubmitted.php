<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\Motion;

class MotionSubmitted extends Base implements IEmailAdmin
{
    protected $motion;

    public function __construct(Motion $motion)
    {
        $this->motion       = $motion;
        $this->consultation = $motion->getMyConsultation();

        parent::__construct();
    }

    public function getEmailAdminText(): string
    {
        // @TODO Use different texts depending on the status

        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this->motion));
        return str_replace(
            ['%TITLE%', '%LINK%', '%INITIATOR%'],
            [$this->motion->getTitleWithIntro(), $motionLink, $this->motion->getInitiatorsStr()],
            \Yii::t('motion', 'submitted_adminnoti_body')
        );
    }

    public function getEmailAdminSubject(): string
    {
        return \Yii::t('motion', 'submitted_adminnoti_title');
    }
}
