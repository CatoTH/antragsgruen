<?php

namespace app\models\settings;

use app\models\exceptions\{ConfigurationError, FormError};

trait JsonConfigTrait
{
    /**
     * @param null|string|array $data
     */
    public function __construct($data)
    {
        $this->setPropertiesFromJSON($data);
    }

    /**
     * @param \ReflectionClass<self> $reflectionClass
     */
    private function propertyIsInt(\ReflectionClass $reflectionClass, string $key): bool
    {
        $propertyType = $reflectionClass->getProperty($key)->getType();
        if (is_a($propertyType, \ReflectionNamedType::class)) {
            $typeName = $propertyType->getName();
        } else {
            // Use the deprecated method in PHP <= 7.4
            /** @var \ReflectionType $propertyType */
            $typeName = trim($propertyType->__toString(), '?');
        }

        return $typeName === 'int';
    }

    /**
     * @param null|string|array $data
     * @throws ConfigurationError
     */
    protected function setPropertiesFromJSON($data): void
    {
        if (!$data) {
            return;
        }
        if (is_array($data)) {
            $dataArr = $data;
        } else {
            $data    = str_replace("\r", "", $data);
            $data    = str_replace(chr(194) . chr(160), " ", $data);
            $dataArr = \ColinODell\Json5\Json5Decoder::decode($data, true);
        }
        if ($dataArr === null) {
            /** @var string|null $data */
            throw new ConfigurationError('Invalid JSON string: ' . $data);
        }

        $reflect = new \ReflectionClass(static::class);
        foreach ($dataArr as $key => $val) {
            if (property_exists($this, $key)) {
                if (is_string($val) && $this->propertyIsInt($reflect, $key)) {
                    // Some database entries have stored integers as string, so let's try to typecast them
                    if ($val === '' && $reflect->getProperty($key)->getType()->allowsNull()) {
                        $this->$key = null;
                    } else {
                        $this->$key = $val;
                    }
                } else {
                    $this->$key = $val;
                }
            }
        }
    }

    public function jsonSerialize(): array
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
