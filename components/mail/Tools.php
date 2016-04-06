<?php

namespace app\components\mail;

use app\models\db\EMailLog;
use app\models\db\Site;
use app\models\exceptions\MailNotSent;
use app\models\exceptions\ServerConfiguration;

class Tools
{
    /**
     * @param int $mailType
     * @param Site|null $fromSite
     * @param string $toEmail
     * @param null|int $toPersonId
     * @param string $subject
     * @param string $textPlain
     * @param string $textHtml
     * @param null|array $noLogReplaces
     * @throws MailNotSent
     */
    public static function sendWithLog(
        $mailType,
        $fromSite,
        $toEmail,
        $toPersonId,
        $subject,
        $textPlain,
        $textHtml = '',
        $noLogReplaces = null
    )
    {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $mailer = Base::createMailer($params->mailService);
        if (!$mailer) {
            throw new MailNotSent('E-Mail not configured');
        }

        $sendTextPlain = ($noLogReplaces ? str_replace(
            array_keys($noLogReplaces),
            array_values($noLogReplaces),
            $textPlain
        ) : $textPlain);
        $sendTextHtml = ($noLogReplaces ? str_replace(
            array_keys($noLogReplaces),
            array_values($noLogReplaces),
            $textHtml
        ) : $textHtml);

        $fromEmail = $params->mailFromEmail;
        $fromName  = $params->mailFromName;
        $replyTo   = '';
        if ($fromSite) {
            if ($fromSite->getSettings()->emailFromName != '') {
                $fromName = $fromSite->getSettings()->emailFromName;
            }
            if ($fromSite->getSettings()->emailReplyTo != '') {
                $replyTo = $fromSite->getSettings()->emailReplyTo;
            }
        }

        $messageId = explode('@', $fromEmail);
        if (count($messageId) == 2) {
            $messageId = uniqid() . '@' . $messageId[1];
        } else {
            $messageId = uniqid() . '@antragsgruen.de';
        }

        $exception = null;
        try {
            $message = $mailer->createMessage(
                $mailType,
                $subject,
                $sendTextPlain,
                $sendTextHtml,
                $fromName,
                $fromEmail,
                $replyTo,
                $messageId
            );
            $status  = $mailer->send($message, $toEmail);
        } catch (\Exception $e) {
            $status    = EMailLog::STATUS_DELIVERY_ERROR;
            $exception = $e;
        }

        $obj = new EMailLog();
        if ($toPersonId) {
            $obj->toUserId = $toPersonId;
        }
        if ($fromSite) {
            $obj->fromSiteId = $fromSite->id;
        }
        $obj->toEmail   = $toEmail;
        $obj->type      = $mailType;
        $obj->fromEmail = mb_encode_mimeheader($fromName) . ' <' . $fromEmail . '>';
        $obj->subject   = $subject;
        $obj->text      = $textPlain;
        $obj->dateSent  = date('Y-m-d H:i:s');
        $obj->status    = $status;
        $obj->messageId = $messageId;
        $obj->save();

        if ($exception) {
            \Yii::error($exception->getMessage());
            /** @var \Exception $exception */
            throw new MailNotSent($exception->getMessage());
        }

        if (YII_ENV == 'test') {
            \yii::$app->session->setFlash('email', 'E-Mail sent to: ' . $toEmail);
        }
    }
}
