<?php

namespace app\components\mail;

use app\models\db\{Consultation, EMailBlacklist, EMailLog};
use app\models\exceptions\ServerConfiguration;

class Mailjet extends Base
{
    private $apiKey;
    private $secret;

    /**
     * @param array $params
     *
     * @throws ServerConfiguration
     */
    public function __construct($params)
    {
        if (!isset($params['apiKey'])) {
            throw new ServerConfiguration('Mailjet\'s apiKey not set');
        }
        $this->apiKey = $params['apiKey'];
        $this->secret = $params['mailjetApiSecret'];
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
        $html = $this->createHtmlPart($subject, $plain, $html, $consultation);

        $message = [
            'From'     => [
                'Email' => $fromEmail,
                'Name'  => $fromName
            ],
            'Subject'  => $subject,
            'TextPart' => $plain,
            'HTMLPart' => $html,
            'Headers'  => [
                'Precedence' => 'bulk',
            ]
        ];
        if ($replyTo) {
            $message['ReplyTo'] = [
                'Email' => $replyTo,
                'Name'  => $replyTo
            ];
        }
        if ($messageId) {
            $message['CustomID'] = $messageId;
        }

        return $message;
    }

    /**
     * @param array $message
     * @param string $toEmail
     *
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

        $message['To'] = [
            [
                'Email' => $toEmail,
                'Name'  => $toEmail,
            ]
        ];
        $mailjet       = new \Mailjet\Client($this->apiKey, $this->secret, true, ['version' => 'v3.1']);
        $response      = $mailjet->post(\Mailjet\Resources::$Email, ['body' => ['Messages' => [$message]]]);
        if ($response->success()) {
            return EMailLog::STATUS_SENT;
        } else {
            var_dump($response->getBody()['Messages'][0]['Errors']);

            return EMailLog::STATUS_DELIVERY_ERROR;
        }
    }


    /**
     * @param int $type
     *
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getMessageClass($type)
    {
        return null;
    }

    /**
     * @return null
     */
    protected function getTransport()
    {
        return null;
    }
}
