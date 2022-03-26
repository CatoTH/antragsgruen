<?php

namespace app\models\exceptions;

class NotAmendable extends ExceptionBase
{
    /** @var bool */
    private $publicMessage;

    public function __construct(string $message, bool $messageIsPublic = false)
    {
        parent::__construct($message);
        $this->publicMessage = $messageIsPublic;
    }

    public function isMessagePublic(): bool
    {
        return $this->publicMessage;
    }
}
