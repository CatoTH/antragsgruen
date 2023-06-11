<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\{Consultation, User};

class UserAsksPermission extends Base implements IEmailAdmin
{
    public function __construct(
        protected User $user,
        protected Consultation $consultation
    ) {
        parent::__construct();
    }

    public function getEmailAdminText(): string
    {
        $actionlink = UrlHelper::absolutizeLink(UrlHelper::createUrl('/admin/users/index'))
            . '#accountsScreenForm';

        return str_replace(
            ['%USERNAME%', '%EMAIL%', '%CONSULTATION%', '%ACTIONLINK%'],
            [$this->user->name, $this->user->getAuthName(), $this->consultation->title, $actionlink],
            \Yii::t('user', 'acc_request_noti_body')
        );
    }

    public function getEmailAdminSubject(): string
    {
        return \Yii::t('user', \Yii::t('user', 'acc_request_noti_subject'));
    }
}
