<?php

namespace app\models\settings;

use app\models\exceptions\ConfigurationError;
use app\models\exceptions\FormError;

trait JsonConfigTrait
{
    /**
     * @var null|string|array @data
     */
    public function __construct($data)
    {
        $this->setPropertiesFromJSON($data);
    }

    /**
     * @var null|string|array @data
     * @throws ConfigurationError
     */
    protected function setPropertiesFromJSON($data)
    {
        if (!$data) {
            return;
        }
        if (is_array($data)) {
            $dataArr = $data;
        } else {
            $data    = str_replace("\r", "", $data);
            $data    = str_replace(chr(194) . chr(160), " ", $data);
            $dataArr = json_decode($data, true);
        }
        if ($dataArr === null) {
            throw new ConfigurationError('Invalid JSON string: ' . $data);
        }

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
     * @throws FormError
     */
    public function saveForm(array $formdata, array $affectedFields): void
    {
        $fields = get_object_vars($this);
        foreach ($affectedFields as $key) {
            if (!array_key_exists($key, $fields)) {
                throw new FormError('Unknown field: ' . $key);
            }
            $val = $fields[$key];
            if (is_bool($val)) {
                $this->$key = (isset($formdata[$key]) && (bool)$formdata[$key]);
            } elseif (is_int($val)) {
                $this->$key = ($formdata[$key] === null ? null : (int)$formdata[$key]);
            } else {
                $this->$key = $formdata[$key];
            }
        }
    }
}
