<?php

namespace app\models\exceptions;


use yii\db\Exception;

class DB extends Exception
{
    private $errors;

    /**
     * @param array $errors
     */
    public function __construct($errors)
    {
        $this->errors = $errors;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return implode("\n", $this->errors);
    }
}
