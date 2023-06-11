<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\Amendment;

class AmendmentWithdrawn extends Base implements IEmailAdmin
{
    public function __construct(
        protected Amendment $amendment
    ) {
        $this->consultation = $amendment->getMyMotion()->getMyConsultation();

        parent::__construct();
    }

    public function getEmailAdminText(): string
    {
        $amendmentLink = UrlHelper::absolutizeLink(UrlHelper::createAmendmentUrl($this->amendment));
        return str_replace(
            ['%TITLE%', '%LINK%', '%INITIATOR%'],
            [$this->amendment->getTitle(), $amendmentLink, $this->amendment->getInitiatorsStr()],
            \Yii::t('amend', 'withdrawn_adminnoti_body')
        );
    }

    public function getEmailAdminSubject(): string
    {
        return \Yii::t('amend', 'withdrawn_adminnoti_title');
    }
}
