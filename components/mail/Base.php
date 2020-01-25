<?php

namespace app\components\mail;

use app\models\db\{EMailBlacklist, EMailLog};
use app\models\exceptions\ServerConfiguration;
use yii\helpers\Html;

abstract class Base
{
    /**
     * @param null|array $params
     * @return Base|null
     * @throws ServerConfiguration
     */
    public static function createMailer($params)
    {
        if (!is_array($params)) {
            return null;
        }
        if (!isset($params['transport'])) {
            throw new ServerConfiguration('Invalid E-Mail configuration');
        }
        switch ($params['transport']) {
            case 'mailgun':
                return new Mailgun($params);
                break;
            case 'mandrill':
                return new Mandrill($params);
                break;
            case 'sendmail':
                return new Sendmail();
                break;
            case 'mailjet':
                return new Mailjet($params);
                break;
            case 'smtp':
                return new SMTP($params);
                break;
            case 'none':
                return new None();
                break;
            default:
                throw new ServerConfiguration('Invalid E-Mail-Transport: ' . $params['transport']);
        }
    }

    /**
     * @param int $type
     * @return \Swift_Message
     */
    abstract protected function getMessageClass($type);

    /**
     * @return \Swift_Mailer|null
     */
    abstract protected function getTransport();

    /**
     * @param int $type
     * @param string $subject
     * @param string $plain
     * @param string $html
     * @param string $fromName
     * @param string $fromEmail
     * @param string|null $replyTo
     * @param string $messageId
     * @return \Swift_Message|array
     */
    public function createMessage(int $type, string $subject, string $plain, string $html, string $fromName, string $fromEmail, ?string $replyTo, $messageId)
    {
        $mail = $this->getMessageClass($type);
        $mail->setFrom([$fromEmail => $fromName]);
        $mail->setSubject($subject);

        if ($html === '') {
            $mail->setBody($plain);
        } else {
            $html = '<!DOCTYPE html><html>
            <head><meta charset="utf-8"><title>' . Html::encode($subject) . '</title>
            </head><body>' . $html . '</body></html>';

            $converter = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
            $contentHtml = $converter->convert($html);
            $contentHtml = preg_replace("/ data\-[a-z0-9_-]+=\"[^\"]*\"/siu", "", $contentHtml);

            $mail->setBody($contentHtml, 'text/html');
            $mail->addPart($plain, 'text/plain');

        }

        if ($replyTo) {
            $mail->setReplyTo($replyTo);
        }
        if ($messageId) {
            $mail->setId($messageId);
        }

        return $mail;
    }

    /**
     * @param \Swift_Message|array $message
     * @param string $toEmail
     * @return string
     */
    public function send($message, $toEmail)
    {
        if (YII_ENV === 'test' || mb_strpos($toEmail, '@example.org') !== false) {
            return EMailLog::STATUS_SKIPPED_OTHER;
        }
        if (EMailBlacklist::isBlacklisted($toEmail)) {
            return EMailLog::STATUS_SKIPPED_BLACKLIST;
        }

        $message->setTo($toEmail);
        $transport = $this->getTransport();
        $transport->send($message);

        return EMailLog::STATUS_SENT;
    }
}
