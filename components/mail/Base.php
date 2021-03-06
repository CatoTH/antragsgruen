<?php

namespace app\components\mail;

use app\components\HTMLTools;
use app\models\settings\AntragsgruenApp;
use app\models\db\{Consultation, EMailBlocklist, EMailLog};
use app\models\exceptions\ServerConfiguration;
use yii\helpers\Html;

abstract class Base
{
    /**
     * @param null|array $params
     *
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
            /*
            case 'mailgun':
                return new Mailgun($params);
                break;
            case 'mandrill':
                return new Mandrill($params);
                break;
            */
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
     *
     * @return \Swift_Message
     */
    abstract protected function getMessageClass($type);

    /**
     * @return \Swift_Mailer|null
     */
    abstract protected function getTransport();

    protected function getFallbackTransport(): ?\Swift_Mailer
    {
        return null;
    }

    protected function createHtmlPart(string $subject, string $plain, ?string $html, ?Consultation $consultation): string
    {
        if (!$html) {
            $html = '<p>' . HTMLTools::plainToHtml($plain) . '</p>';
        }

        $template = '@app/views/layouts/email';
        foreach (AntragsgruenApp::getActivePlugins() as $pluginId => $pluginClass) {
            if ($pluginClass::getCustomEmailTemplate()) {
                $template = $pluginClass::getCustomEmailTemplate();
            }
        }

        return \Yii::$app->controller->renderPartial($template, [
            'title'  => $subject,
            'html'   => $html,
            'styles' => ($consultation ? $consultation->site->getSettings()->getStylesheet() : null),
        ]);
    }

    public function createMessage(
        int $type,
        string $subject,
        string $plain,
        string $html,
        string $fromName,
        string $fromEmail,
        ?string $replyTo,
        string $messageId,
        ?Consultation $consultation
    ) {
        $mail = $this->getMessageClass($type);
        $mail->setFrom([$fromEmail => $fromName]);
        $mail->setSubject($subject);

        $html = $this->createHtmlPart($subject, $plain, $html, $consultation);

        $html = '<!DOCTYPE html><html>
            <head><meta charset="utf-8"><title>' . Html::encode($subject) . '</title>
            </head><body>' . $html . '</body></html>';

        $converter   = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
        $contentHtml = $converter->convert($html);
        $contentHtml = preg_replace("/ data\-[a-z0-9_-]+=\"[^\"]*\"/siu", "", $contentHtml);

        $mail->setBody($contentHtml, 'text/html');
        $mail->addPart($plain, 'text/plain');

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
     *
     * @return int
     */
    public function send($message, $toEmail)
    {
        if (YII_ENV === 'test' || mb_strpos($toEmail, '@example.org') !== false) {
            return EMailLog::STATUS_SKIPPED_OTHER;
        }
        if (EMailBlocklist::isBlocked($toEmail)) {
            return EMailLog::STATUS_SKIPPED_BLOCKLIST;
        }

        $message->setTo($toEmail);
        try {
            $transport = $this->getTransport();
            $transport->send($message);
        } catch (\Exception $e) {
            $fallbackTransport = $this->getFallbackTransport();
            // "Expected response code 220 but got an empty response" is triggered is regular sendmail is not accessible
            if ($fallbackTransport && strpos($e->getMessage(), 'Expected response code 220') !== false) {
                $fallbackTransport->send($message);
            }
        }

        return EMailLog::STATUS_SENT;
    }
}
