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
        parent::__construct(implode("\n", $errors));
        $this->errors = $errors;
    }

    /**
     * @return string
     */
    public function __toString()
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
