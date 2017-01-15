<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\Motion;

class MotionWithdrawn extends Base implements IEmailAdmin
{
    protected $motion;

    /**
     * MotionWithdrawn constructor.
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
        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this->motion));
        return str_replace(
            ['%TITLE%', '%LINK%', '%INITIATOR%'],
            [$this->motion->title, $motionLink, $this->motion->getInitiatorsStr()],
            \Yii::t('motion', 'withdrawn_adminnoti_body')
        );
    }

    /**
     * @return string
     */
    public function getEmailAdminTitle()
    {
        return \Yii::t('motion', 'withdrawn_adminnoti_title');
    }
}
