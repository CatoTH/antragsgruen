<?php

namespace app\models\notifications;

use app\components\UrlHelper;
use app\models\db\Consultation;
use app\models\db\User;

class UserAsksPermission extends Base implements IEmailAdmin
{
    protected $user;
    protected $consultation;

    /**
     * UserAsksPermission constructor.
     * @param User $user
     * @param Consultation $consultation
     */
    public function __construct(User $user, Consultation $consultation)
    {
        $this->user         = $user;
        $this->consultation = $consultation;

        parent::__construct();
    }

    /**
     * @return string
     */
    public function getEmailAdminText()
    {
        $actionlink = UrlHelper::absolutizeLink(UrlHelper::createUrl('/admin/index/siteaccess'))
            . '#accountsScreenForm';

        return str_replace(
            ['%USERNAME%', '%EMAIL%', '%CONSULTATION%', '%ACTIONLINK%'],
            [$this->user->name, $this->user->getAuthName(), $this->consultation->title, $actionlink],
            \Yii::t('user', 'acc_request_noti_body')
        );
    }

    /**
     * @return string
     */
    public function getEmailAdminSubject()
    {
        return \Yii::t('user', \Yii::t('user', 'acc_request_noti_subject'));
    }
}
