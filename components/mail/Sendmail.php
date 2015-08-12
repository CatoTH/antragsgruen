<?php

namespace app\components\mail;

class Sendmail extends Base
{
    /**
     * @param int $type
     * @return \Zend\Mail\Message
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMessageClass($type)
    {
        return new \Zend\Mail\Message();
    }

    /**
     * @return \Zend\Mail\Transport\TransportInterface
     */
    public function getTransport()
    {
        return new \Zend\Mail\Transport\Sendmail();
    }
}
