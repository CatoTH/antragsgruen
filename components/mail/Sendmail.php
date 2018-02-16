<?php

namespace app\components\mail;

class Sendmail extends Base
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
     * @return \Zend\Mail\Transport\TransportInterface
     */
    protected function getTransport()
    {
        return new \Zend\Mail\Transport\Sendmail();
    }
}
