<?php

namespace app\components\mail;

use app\models\db\EMailLog;

class None extends Base
{
    protected function getMessageClass($type)
    {
        return new \Swift_Message();
    }

    /**
     * @return null
     */
    protected function getTransport()
    {
        return null;
    }

    /**
     * @param \Swift_Message $message
     * @param string $toEmail
     * @return string
     */
    public function send($message, $toEmail)
    {
        return EMailLog::STATUS_SKIPPED_OTHER;
    }
}
