<?php

namespace app\models\notifications;

use app\components\mail\Tools;
use app\components\RequestContext;
use app\models\db\Consultation;
use app\models\db\EMailLog;
use app\models\exceptions\MailNotSent;
use app\models\exceptions\ServerConfiguration;

abstract class Base
{
    protected Consultation $consultation;

    public function __construct()
    {
        $this->send();
    }

    private function sendEmailAdmin(): void
    {
        $consultation = $this->consultation;
        $mails        = $consultation->getAdminEmails();

        /** @var IEmailAdmin $maildata */
        $maildata = $this;
        foreach ($mails as $mail) {
            try {
                Tools::sendWithLog(
                    EMailLog::TYPE_MOTION_NOTIFICATION_ADMIN,
                    $consultation,
                    trim($mail),
                    null,
                    $maildata->getEmailAdminSubject(),
                    $maildata->getEmailAdminText()
                );
            } catch (MailNotSent | ServerConfiguration $e) {
                $errMsg = \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage();
                RequestContext::getSession()->setFlash('error', $errMsg);
            }
        }
    }

    private function sendEmailUser(): void
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

    public function send(): void
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
