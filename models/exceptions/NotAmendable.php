<?php

namespace app\models\exceptions;

class NotAmendable extends ExceptionBase
{
    private $publicMessage = false;

    /**
     * NotAmendable constructor.
     * @param string $message
     * @param bool $messageIsPublic
     */
    public function __construct($message, $messageIsPublic = false)
    {
        parent::__construct($message);
        $this->publicMessage = $messageIsPublic;
    }

    /**
     * @return bool
     */
    public function isMessagePublic()
    {
        return $this->publicMessage;
    }
}
