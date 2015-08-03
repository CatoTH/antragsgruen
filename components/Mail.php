<?php

namespace app\components;

use app\models\db\EMailBlacklist;
use app\models\db\EMailLog;
use app\models\db\Site;

class Mail
{
    /**
     * @param int $type
     * @return \Zend\Mail\Message
     */
    public static function getMessageClass($type)
    {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;

        if ($params->mandrillApiKey) {
            $message = new \SlmMail\Mail\Message\Mandrill();
            $message->addTag(EMailLog::$MANDRILL_TAGS[$type]);
            $message->setOptions([
                'important'         => false,
                'track_clicks'      => false,
                'track_opens'       => false,
                'inline_css'        => true,
                'view_content_link' => false,
            ]);
            return $message;
        } else {
            return new \Zend\Mail\Message();
        }
    }

    /**
     * @return \Zend\Mail\Transport\TransportInterface
     */
    public static function getTransport()
    {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;

        if ($params->mandrillApiKey) {
            $client = new \Zend\Http\Client();
            $client->setAdapter(new \Zend\Http\Client\Adapter\Curl());
            $service = new \SlmMail\Service\MandrillService($params->mandrillApiKey);
            $service->setClient($client);
            return new \SlmMail\Mail\Transport\HttpTransport($service);
        } else {
            return new \Zend\Mail\Transport\Sendmail();
        }
    }

    /**
     * @param int $type
     * @param string $subject
     * @param string $plain
     * @param string $html
     * @param string $fromName
     * @param string $fromEmail
     * @param string $replyTo
     * @param string $messageId
     * @return \Zend\Mail\Message
     */
    public static function createMessage($type, $subject, $plain, $html, $fromName, $fromEmail, $replyTo, $messageId)
    {
        $mail = static::getMessageClass($type);
        $mail->setFrom($fromEmail, $fromName);
        $mail->setSubject($subject);
        $mail->setEncoding('UTF-8');

        $mId = new \Zend\Mail\Header\MessageId();
        $mId->setId($messageId);
        $mail->getHeaders()->addHeader($mId);

        if ($html == '') {
            $mail->setBody($plain);
            $content = new \Zend\Mail\Header\ContentType();
            $content->setType('text/plain');
            $content->addParameter('charset', 'UTF-8');
            $mail->getHeaders()->addHeader($content);
        } else {
            $converter = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles($html);
            $converter->setStripOriginalStyleTags(true);
            $converter->setUseInlineStylesBlock(true);
            $converter->setEncoding('UTF-8');
            $converter->setCleanup(false);
            $converter->setExcludeMediaQueries(true);
            $contentHtml = $converter->convert();
            $contentHtml = preg_replace("/ data\-[a-z0-9_-]+=\"[^\"]*\"/siu", "", $contentHtml);

            $textPart          = new \Zend\Mime\Part($plain);
            $textPart->type    = 'text/plain';
            $textPart->charset = 'UTF-8';
            $htmlPart          = new \Zend\Mime\Part($contentHtml);
            $htmlPart->type    = 'text/html';
            $htmlPart->charset = 'UTF-8';
            $mimem             = new \Zend\Mime\Message();
            $mimem->setParts([$textPart, $htmlPart]);

            $mail->setBody($mimem);
            $mail->getHeaders()->get('content-type')->setType('multipart/alternative');
        }

        if ($replyTo != '') {
            $reply_to_head = new \Zend\Mail\Header\ReplyTo();
            $reply_to_addr = new \Zend\Mail\AddressList();
            $reply_to_addr->add('tobias2@hoessl.eu');
            $reply_to_head->setAddressList($reply_to_addr);
            $mail->getHeaders()->addHeader($reply_to_head);
        }


        return $mail;
    }

    /**
     * @param \Zend\Mail\Message $message
     * @param string $toEmail
     * @return string
     */
    public static function send($message, $toEmail)
    {
        if (YII_ENV == 'test' || mb_strpos($toEmail, '@example.org') !== false) {
            return EMailLog::STATUS_SKIPPED_OTHER;
        }
        if (EMailBlacklist::isBlacklisted($toEmail)) {
            return EMailLog::STATUS_SKIPPED_BLACKLIST;
        }

        $message->setTo($toEmail);
        $transport = static::getTransport();
        $transport->send($message);

        return EMailLog::STATUS_SENT;
    }

    /**
     * @param int $mailType
     * @param Site|null $fromSite
     * @param string $toEmail
     * @param null|int $toPersonId
     * @param string $subject
     * @param string $text
     * @param null|string $fromName
     * @param null|string $fromEmail
     * @param null|array $noLogReplaces
     */
    public static function sendWithLog(
        $mailType,
        $fromSite,
        $toEmail,
        $toPersonId,
        $subject,
        $text,
        $fromName = null,
        $fromEmail = null,
        $noLogReplaces = null
    )
    {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;

        $sendText = ($noLogReplaces ? str_replace(
            array_keys($noLogReplaces),
            array_values($noLogReplaces),
            $text
        ) : $text);

        $messageId = explode('@', $fromEmail);
        if (count($messageId) == 2) {
            $messageId = uniqid() . '@' . $messageId[1];
        } else {
            $messageId = uniqid() . '@antragsgruen.de';
        }

        if ($fromName === null) {
            $fromName = $params->mailFromName;
        }
        if ($fromEmail === null) {
            $fromEmail = $params->mailFromEmail;
        }

        // @TODO: Reply-To

        try {
            $message = static::createMessage($mailType, $subject, $sendText, '', $fromName, $fromEmail, '', $messageId);
            $status  = static::send($message, $toEmail);
        } catch (\Exception $e) {
            $status = EMailLog::STATUS_DELIVERY_ERROR;
            \yii::$app->session->setFlash('error', 'Eine E-Mail konnte nicht geschickt werden: ' . $e->getMessage());
        }

        $obj = new \app\models\db\EMailLog();
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
