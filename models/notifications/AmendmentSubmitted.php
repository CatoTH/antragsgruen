<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\Amendment;

class AmendmentSubmitted extends Base implements IEmailAdmin
{
    /** @var Amendment */
    protected $amendment;

    /**
     * MotionInitiallySubmitted constructor.
     * @param Amendment $amendment
     */
    public function __construct(Amendment $amendment)
    {
        $this->amendment       = $amendment;
        $this->consultation = $amendment->getMyMotion()->getMyConsultation();

        parent::__construct();
    }

    /**
     * @return string
     */
    public function getEmailAdminText()
    {
        // @TODO Use different texts depending on the status

        $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this->amendment));
        return str_replace(
            ['%TITLE%', '%LINK%', '%INITIATOR%'],
            [$this->amendment->getTitle(), $amendmentLink, $this->amendment->getInitiatorsStr()],
            \Yii::t('amend', 'submitted_adminnoti_body')
        );
    }

    /**
     * @return string
     */
    public function getEmailAdminTitle()
    {
        return \Yii::t('amend', 'submitted_adminnoti_title');
    }
}
