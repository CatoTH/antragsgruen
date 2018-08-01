<?php

namespace app\models\settings;

use app\models\exceptions\ConfigurationError;
use app\models\exceptions\FormError;

trait JsonConfigTrait
{
    /**
     * @param string|array|null $data
     */
    public function __construct($data)
    {
        if (is_array($data)) {
            $this->setPropertiesFromArray($data);
        } elseif (is_string($data)) {
            $this->setPropertiesFromJSON($data);
        }
    }

    /**
     * @param string $data
     * @throws ConfigurationError
     */
    protected function setPropertiesFromJSON($data)
    {
        if ($data == '') {
            return;
        }
        $data    = str_replace("\r", "", $data);
        $data    = str_replace(chr(194) . chr(160), " ", $data);
        $dataArr = json_decode($data, true);
        if ($dataArr === null) {
            throw new ConfigurationError('Invalid JSON string: ' . $data);
        }

        $this->setPropertiesFromArray($dataArr);
    }

    /**
     * @param array $dataArr
     */
    protected function setPropertiesFromArray($dataArr)
    {
        foreach ($dataArr as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
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
