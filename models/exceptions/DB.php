<?php

namespace app\models\exceptions;

class DB extends ExceptionBase
{
    private array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct($this->__toString());
    }

    public function __toString(): string
    {
        $str = '';
        foreach ($this->errors as $errKey => $err) {
            if ($str !== '') {
                $str .= "\n";
            }
            if (is_array($err)) {
                $str .= $errKey . ': ' . implode(', ', $err);
            } else {
                $str .= $err;
            }
        }
        return $str;
    }
}
