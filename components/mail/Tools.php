<?php

namespace app\components\mail;

use app\models\db\EMailLog;
use app\models\db\Site;

class Tools
{
    /**
     * @param int $mailType
     * @param Site|null $fromSite
     * @param string $toEmail
     * @param null|int $toPersonId
     * @param string $subject
     * @param string $text
     * @param null|array $noLogReplaces
     */
    public static function sendWithLog(
        $mailType,
        $fromSite,
        $toEmail,
        $toPersonId,
        $subject,
        $text,
        $noLogReplaces = null
    ) {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $mailer = Base::createMailer($params->mailService);
        if (!$mailer) {
            return;
        }

        $sendText = ($noLogReplaces ? str_replace(
            array_keys($noLogReplaces),
            array_values($noLogReplaces),
            $text
        ) : $text);

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

        try {
            $message = $mailer->createMessage(
                $mailType,
                $subject,
                $sendText,
                '',
                $fromName,
                $fromEmail,
                $replyTo,
                $messageId
            );
            $status  = $mailer->send($message, $toEmail);
        } catch (\Exception $e) {
            $status = EMailLog::STATUS_DELIVERY_ERROR;
            \yii::$app->session->setFlash('error', \Yii::t('base', 'err_email_not_sent') . ': ' . $e->getMessage());
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
        $obj->text      = $text;
        $obj->dateSent  = date('Y-m-d H:i:s');
        $obj->status    = $status;
        $obj->messageId = $messageId;
        $obj->save();

        if (YII_ENV == 'test') {
            \yii::$app->session->setFlash('email', 'E-Mail sent to: ' . $toEmail);
        }
    }
}
