<?php

namespace app\components\mail;

use app\models\db\EMailBlacklist;
use app\models\db\EMailLog;
use app\models\exceptions\ServerConfiguration;
use yii\helpers\Html;
use Zend\Mail\Header\ContentType;

abstract class Base
{
    /**
     * @param null|array $params
     * @return Mandrill|\app\components\mail\Sendmail|null
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
                return new \app\components\mail\Sendmail($params);
                break;
            case 'smtp':
                return new SMTP($params);
                break;
            case 'none':
                return new None($params);
                break;
            default:
                throw new ServerConfiguration('Invalid E-Mail-Transport: ' . $params['transport']);
        }
    }

    /**
     * @param int $type
     * @return \Zend\Mail\Message
     */
    abstract public function getMessageClass($type);

    /**
     * @return \Zend\Mail\Transport\TransportInterface|null
     */
    abstract public function getTransport();

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
    public function createMessage($type, $subject, $plain, $html, $fromName, $fromEmail, $replyTo, $messageId)
    {
        $mail = $this->getMessageClass($type);
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
            $html = '<!DOCTYPE html><html>
            <head><meta charset="utf-8"><title>' . Html::encode($subject) . '</title>
            </head><body>' . $html . '</body></html>';
            
            $converter = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
            $contentHtml = $converter->convert($html);
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
            /** @var ContentType $contentType */
            $contentType = $mail->getHeaders()->get('content-type');
            $contentType->setType('multipart/alternative');
        }

        if ($replyTo != '') {
            $reply_to_head = new \Zend\Mail\Header\ReplyTo();
            $reply_to_addr = new \Zend\Mail\AddressList();
            $reply_to_addr->add($replyTo);
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
    public function send($message, $toEmail)
    {
        if (YII_ENV == 'test' || mb_strpos($toEmail, '@example.org') !== false) {
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
