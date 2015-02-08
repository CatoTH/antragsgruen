<?php

namespace app\models\exceptions;


use yii\db\Exception;

class DB extends Exception
{
    private $errors;

    public function __construct($errors)
    {
        $this->errors = $errors;
    }

    public function __toString()
    {
        return implode("\n", $this->errors);
    }
}
