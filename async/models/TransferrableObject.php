<?php

namespace app\async\models;

abstract class TransferrableObject implements \JsonSerializable
{
    /**
     * @param string|array|null $data
     * @throws \Exception
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
     * @throws \Exception
     */
    protected function setPropertiesFromJSON($data)
    {
        if ($data === '') {
            return;
        }
        $dataArr = json_decode($data, true);
        if ($dataArr === null) {
            throw new \Exception('Invalid JSON string: ' . $data);
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
}
