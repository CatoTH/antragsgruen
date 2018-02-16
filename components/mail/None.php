<?php

namespace app\components\mail;

use app\models\db\EMailLog;

class None extends Base
{
    /**
     * @param int $type
     * @return \Zend\Mail\Message
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getMessageClass($type)
    {
        return new \Zend\Mail\Message();
    }

    /**
     * @return null
     */
    protected function getTransport()
    {
        return null;
    }

    /**
     * @param \Zend\Mail\Message $message
     * @param string $toEmail
     * @return string
     */
    public function send($message, $toEmail)
    {
        return EMailLog::STATUS_SKIPPED_OTHER;
    }
}
