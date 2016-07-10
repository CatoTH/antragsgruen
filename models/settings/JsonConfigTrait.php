<?php

namespace app\models\settings;

use app\models\exceptions\FormError;
use app\models\exceptions\Internal;

trait JsonConfigTrait
{
    /**
     * @param string|null $data
     * @throws \Exception
     */
    public function __construct($data)
    {
        $this->setPropertiesFromJSON($data);
    }

    /**
     * @param string $data
     * @throws \Exception
     */
    protected function setPropertiesFromJSON($data)
    {
        if ($data == '') {
            return;
        }
        $dataArr = json_decode($data, true);
        if ($dataArr === null) {
            throw new \Exception('Invalid JSON string: ' . $data);
        }

        foreach ($dataArr as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode(get_object_vars($this), JSON_PRETTY_PRINT);
    }

    /**
     * @param array $formdata
     * @param array $affectedFields
     * @throws FormError
     */
    public function saveForm($formdata, $affectedFields)
    {
        $fields = get_object_vars($this);
        foreach ($affectedFields as $key) {
            if (!array_key_exists($key, $fields)) {
                throw new FormError('Unknown field: ' . $key);
            }
            $val = $fields[$key];
            if ($key == 'forceMotion') {
                if (isset($formdata['singleMotionMode'])) {
                    $this->forceMotion = (int)$formdata[$key];
                } else {
                    $this->forceMotion = null;
                }
            } elseif (is_bool($val)) {
                $this->$key = (isset($formdata[$key]) && (bool)$formdata[$key]);
            } elseif (is_int($val)) {
                $this->$key = (int)$formdata[$key];
            } else {
                $this->$key = $formdata[$key];
            }
        }
    }
}
