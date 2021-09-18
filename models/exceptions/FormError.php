<?php

namespace app\models\exceptions;

class FormError extends ExceptionBase
{
    /** @var string[] */
    protected $messages;

    /**
     * @param string|string[]|string[][] $messages
     */
    public function __construct($messages)
    {
        if (is_array($messages)) {
            $this->messages = $messages;
        } else {
            $this->messages = [$messages];
        }
        foreach ($this->messages as $i => $mess) {
            if (is_array($mess)) {
                $this->messages[$i] = implode("\n", $mess);
            }
        }
        parent::__construct(implode("\n", $this->messages));
    }

    /**
     * @return string[]
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
