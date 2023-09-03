<?php

namespace app\components\mail;

use app\components\RequestContext;
use app\models\settings\AntragsgruenApp;
use app\models\db\{Consultation, EMailLog, IMotion, User};
use app\models\exceptions\{MailNotSent, ServerConfiguration};
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class Tools
{
    public static function getDefaultMailFromName(?Consultation $consultation = null): string
    {
        $name = AntragsgruenApp::getInstance()->mailFromName;
        if ($consultation && $consultation->getSettings()->emailFromName) {
            $name = $consultation->getSettings()->emailFromName;
        }
        return $name;
    }

    public static function getDefaultReplyTo(?IMotion $imotion = null, ?Consultation $consultation = null, ?User $user = null): ?string
    {
        if ($imotion && $imotion->responsibilityId && $imotion->responsibilityUser && $imotion->responsibilityUser->getSettingsObj()->ppReplyTo) {
            return $imotion->responsibilityUser->getSettingsObj()->ppReplyTo;
        }

        if ($user && $user->getSettingsObj()->ppReplyTo) {
            return $user->getSettingsObj()->ppReplyTo;
        }

        $replyTo = null;
        if ($imotion && $imotion->getMyConsultation()) {
            $consultation = $imotion->getMyConsultation();
        }
        if ($consultation) {
            if ($consultation->getSettings()->emailReplyTo) {
                $replyTo = $consultation->getSettings()->emailReplyTo;
            } elseif (AntragsgruenApp::getInstance()->multisiteMode && $consultation->adminEmail) {
                $adminEmails = $consultation->getAdminEmails();
                if (count($adminEmails) > 0) {
                    $replyTo = $adminEmails[0];
                }
            }
        }
        return $replyTo;
    }

    /**
     * @throws MailNotSent
     * @throws ServerConfiguration
     */
    public static function sendWithLog(
        int $mailType,
        ?Consultation $fromConsultation,
        string $toEmail,
        ?int $toPersonId,
        string $subject,
        string $textPlain,
        string $textHtml = '',
        ?array $noLogReplaces = null,
        ?string $fromName = null,
        ?string $replyTo = null
    ): void {
        $params = AntragsgruenApp::getInstance();
        $mailer = Base::createMailer($params->mailService);
        if (!$mailer) {
            throw new MailNotSent('E-Mail not configured');
        }

        $sendTextPlain = ($noLogReplaces ? str_replace(
            array_keys($noLogReplaces),
            array_values($noLogReplaces),
            $textPlain
        ) : $textPlain);
        $sendTextHtml  = ($noLogReplaces ? str_replace(
            array_keys($noLogReplaces),
            array_values($noLogReplaces),
            $textHtml
        ) : $textHtml);

        $fromEmail = $params->mailFromEmail;
        if (!$fromName) {
            $fromName = static::getDefaultMailFromName($fromConsultation);
        }
        if (!$replyTo) {
            $replyTo = static::getDefaultReplyTo(null, $fromConsultation);
        }

        $exception = null;
        $messageId = '';
        try {
            $message = $mailer->createMessage(
                $subject,
                $sendTextPlain,
                $sendTextHtml,
                $fromName,
                $fromEmail,
                $replyTo,
                $fromConsultation
            );
            $result  = $mailer->send($message, $toEmail);
            if (is_string($result)) {
                $status = EMailLog::STATUS_SENT;
                $messageId = $result;
            } else {
                $status = $result;
            }
        } catch (TransportExceptionInterface $e) {
            $status    = EMailLog::STATUS_DELIVERY_ERROR;
            $exception = $e;
        }

        $obj = new EMailLog();
        if ($toPersonId) {
            $obj->toUserId = $toPersonId;
        }
        if ($fromConsultation) {
            $obj->fromSiteId = $fromConsultation->siteId;
        }
        $obj->toEmail   = $toEmail;
        $obj->type      = $mailType;
        $obj->fromEmail = $fromName . ' <' . $fromEmail . '>';
        $obj->subject   = mb_substr($subject, 0, 190);
        $obj->text      = $textPlain;
        $obj->dateSent  = date('Y-m-d H:i:s');
        $obj->status    = $status;
        $obj->messageId = $messageId;
        $obj->save();

        if ($exception) {
            \Yii::error($exception->getMessage());
            throw new MailNotSent($exception->getMessage());
        }

        if (YII_ENV === 'test') {
            $pre = RequestContext::getSession()->getFlash('email', '');
            RequestContext::getSession()->setFlash('email', $pre . 'E-Mail sent to: ' . $toEmail . " (Type $mailType)\n");
        }
    }
}
