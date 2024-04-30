<?php

declare(strict_types=1);

namespace app\components\mail;

use app\components\HTMLTools;
use app\models\settings\AntragsgruenApp;
use app\models\db\{Consultation, EMailBlocklist, EMailLog};
use app\models\exceptions\ServerConfiguration;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\{Address, Email};
use yii\helpers\Html;

abstract class Base
{
    /**
     * @throws ServerConfiguration
     */
    public static function createMailer(?array $params): ?Base
    {
        if (!is_array($params)) {
            return null;
        }
        if (!isset($params['transport'])) {
            throw new ServerConfiguration('Invalid E-Mail configuration');
        }

        return match ($params['transport']) {
            'sendmail' => new Sendmail($params),
            'mailjet' => new Mailjet($params),
            'smtp' => new SMTP($params),
            'ses' => new AmazonSES($params),
            'none' => new None(),
            default => throw new ServerConfiguration('Invalid E-Mail-Transport: ' . $params['transport']),
        };
    }

    abstract protected function getTransport(): ?TransportInterface;

    protected function getFallbackTransport(): ?TransportInterface
    {
        return null;
    }

    protected function createHtmlPart(string $subject, string $plain, ?string $html, ?Consultation $consultation): string
    {
        if (!$html) {
            $html = '<p>' . HTMLTools::plainToHtml($plain) . '</p>';
        }

        $template = '@app/views/layouts/email';
        foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
            if ($pluginClass::getCustomEmailTemplate()) {
                $template = $pluginClass::getCustomEmailTemplate();
            }
        }

        return \Yii::$app->controller->renderPartial($template, [
            'title'  => $subject,
            'html'   => $html,
            'styles' => $consultation?->site->getSettings()->getStylesheet(),
        ]);
    }

    public function createMessage(
        string $subject,
        string $plain,
        string $html,
        string $fromName,
        string $fromEmail,
        ?string $replyTo,
        ?Consultation $consultation
    ): Email {
        $mail = (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->subject($subject);

        $html = $this->createHtmlPart($subject, $plain, $html, $consultation);

        $html = '<!DOCTYPE html><html>
            <head><meta charset="utf-8"><title>' . Html::encode($subject) . '</title>
            </head><body>' . $html . '</body></html>';

        $converter   = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
        $contentHtml = $converter->convert($html);
        $contentHtml = preg_replace("/ data-[a-z0-9_-]+=\"[^\"]*\"/siu", "", $contentHtml);

        $mail->text($plain);
        $mail->html($contentHtml);

        if ($replyTo) {
            $mail->replyTo($replyTo);
        }

        return $mail;
    }

    /**
     * - int for error codes
     * - string: messageId if successful
     *
     * @throws TransportExceptionInterface
     */
    public function send(Email $message, string $toEmail): string|int
    {
        if (YII_ENV === 'test' || str_contains($toEmail, '@example.org')) {
            return EMailLog::STATUS_SKIPPED_OTHER;
        }
        if (EMailBlocklist::isBlocked($toEmail)) {
            return EMailLog::STATUS_SKIPPED_BLOCKLIST;
        }

        $message->to($toEmail);
        try {
            $transport = $this->getTransport();
            $sentMessage = $transport->send($message);

            return $sentMessage->getMessageId();
        } catch (TransportExceptionInterface $e) {
            $fallbackTransport = $this->getFallbackTransport();
            // "Expected response code 220 but got an empty response" is triggered is regular sendmail is not accessible
            if ($fallbackTransport && str_contains($e->getMessage(), 'Expected response code 220')  ) {
                $sentMessage = $fallbackTransport->send($message);

                return $sentMessage->getMessageId();
            } else {
                throw $e;
            }
        }
    }
}
