<?php

namespace app\models\notifications;

use app\components\mail\Tools;
use app\models\db\Consultation;
use app\models\db\EMailLog;
use app\models\exceptions\MailNotSent;

abstract class Base
{
    /** @var Consultation $consultation */
    protected $consultation;

    /**
     * Base constructor.
     */
    public function __construct()
    {
        $this->send();
    }

    /**
     */
    private function sendEmailAdmin()
    {
        $consultation = $this->consultation;
        $mails        = explode(',', $consultation->adminEmail);

        /** @var IEmailAdmin $maildata */
        $maildata = $this;
        foreach ($mails as $mail) {
            if (trim($mail) != '') {
                try {
                    Tools::sendWithLog(
                        EMailLog::TYPE_MOTION_NOTIFICATION_ADMIN,
                        $consultation,
                        trim($mail),
                        null,
                        $maildata->getEmailAdminSubject(),
                        $maildata->getEmailAdminText()
                    );
                } catch (MailNotSent $e) {
                    $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                    \yii::$app->session->setFlash('error', $errMsg);
                }
            }
        }
    }

    /**
     */
    private function sendEmailUser()
    {
        /** @var IEmailUser $maildata */
        $maildata = $this;

        $maildata->getEmailUser()->notificationEmail(
            $this->consultation,
            $maildata->getEmailUserSubject(),
            $maildata->getEmailUserText(),
            $maildata->getEmailUserType()
        );
    }

    /**
     */
    public function send()
    {
        $implements = class_implements($this);
        if (in_array(IEmailAdmin::class, $implements)) {
            $this->sendEmailAdmin();
        }
        if (in_array(IEmailUser::class, $implements)) {
            $this->sendEmailUser();
        }
    }
}
