<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\Motion;

class MotionSubmitted extends Base implements IEmailAdmin
{
    protected $motion;

    /**
     * MotionSubmitted constructor.
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
        // @TODO Use different texts depending on the status

        $motionLink = UrlHelper::absolutizeLink(UrlHelper::createMotionUrl($this->motion));
        return str_replace(
            ['%TITLE%', '%LINK%', '%INITIATOR%'],
            [$this->motion->title, $motionLink, $this->motion->getInitiatorsStr()],
            \Yii::t('motion', 'submitted_adminnoti_body')
        );
    }

    /**
     * @return string
     */
    public function getEmailAdminSubject()
    {
        return \Yii::t('motion', 'submitted_adminnoti_title');
    }
}
