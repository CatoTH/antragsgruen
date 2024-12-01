<?php

declare(strict_types=1);

namespace app\models\backgroundJobs;

use app\components\mail\Base;
use app\components\RequestContext;
use app\models\db\Consultation;
use app\models\db\EMailLog;
use app\models\exceptions\MailNotSent;
use app\models\settings\AntragsgruenApp;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SendNotification extends IBackgroundJob
{
    public const TYPE_ID = 'SEND_NOTIFICATION';

    public function __construct(
        ?Consultation $consultation,
        public int $mailType,
        public string $toEmail,
        public ?int $toPersonId,
        public string $subject,
        public string $textPlain,
        public string $textHtml,
        public ?array $noLogReplaces,
        public string $fromEmail,
        public string $fromName,
        public ?string $replyTo
    ) {
        $this->consultation = $consultation;
    }

    public function getTypeId(): string
    {
        return self::TYPE_ID;
    }

    public function execute(): void
    {
        file_put_contents('/tmp/sendmail.log', 'SEND MAIL: ' . $this->toEmail . PHP_EOL, FILE_APPEND);

        $params = AntragsgruenApp::getInstance();
        $mailer = Base::createMailer($params->mailService);
        if (!$mailer) {
            throw new MailNotSent('E-Mail not configured');
        }

        $sendTextPlain = ($this->noLogReplaces ? str_replace(
            array_keys($this->noLogReplaces),
            array_values($this->noLogReplaces),
            $this->textPlain
        ) : $this->textPlain);
        $sendTextHtml  = ($this->noLogReplaces ? str_replace(
            array_keys($this->noLogReplaces),
            array_values($this->noLogReplaces),
            $this->textHtml
        ) : $this->textHtml);

        $exception = null;
        $messageId = '';
        try {
            $message = $mailer->createMessage(
                $this->subject,
                $sendTextPlain,
                $sendTextHtml,
                $this->fromName,
                $this->fromEmail,
                $this->replyTo,
                $this->consultation
            );
            $result  = $mailer->send($message, $this->toEmail);
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
        if ($this->toPersonId) {
            $obj->toUserId = $this->toPersonId;
        }
        if ($this->consultation) {
            $obj->fromSiteId = $this->consultation->siteId;
        }
        $obj->toEmail   = $this->toEmail;
        $obj->type      = $this->mailType;
        $obj->fromEmail = $this->fromName . ' <' . $this->fromEmail . '>';
        $obj->subject   = mb_substr($this->subject, 0, 190);
        $obj->text      = $this->textPlain;
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
            RequestContext::getSession()->setFlash('email', $pre . 'E-Mail sent to: ' . $this->toEmail . " (Type $this->mailType)\n");
        }
    }
}
