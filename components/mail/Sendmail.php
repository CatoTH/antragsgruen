<?php

namespace app\components\mail;

class Sendmail extends Base
{
    /**
     * @param int $type
     *
     * @return \Swift_Message
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getMessageClass($type)
    {
        return new \Swift_Message();
    }

    /**
     * @return \Swift_Mailer
     */
    protected function getTransport()
    {
        $transport = new \Swift_SendmailTransport('/usr/sbin/sendmail -t');

        return new \Swift_Mailer($transport);
    }
}
