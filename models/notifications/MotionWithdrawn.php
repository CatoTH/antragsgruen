<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\Motion;

class MotionWithdrawn extends Base implements IEmailAdmin
{
    public function __construct(
        protected Motion $motion
    ) {
        $this->consultation = $motion->getMyConsultation();

        parent::__construct();
    }

    public function getEmailAdminText(): string
    {
        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this->motion));
        return str_replace(
            ['%TITLE%', '%LINK%', '%INITIATOR%'],
            [$this->motion->getTitleWithIntro(), $motionLink, $this->motion->getInitiatorsStr()],
            \Yii::t('motion', 'withdrawn_adminnoti_body')
        );
    }

    public function getEmailAdminSubject(): string
    {
        return \Yii::t('motion', 'withdrawn_adminnoti_title');
    }
}
