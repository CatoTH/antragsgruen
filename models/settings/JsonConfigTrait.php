<?php

namespace app\models\settings;

use app\models\exceptions\{ConfigurationError, FormError};
use Symfony\Component\Serializer\Annotation\Ignore;

trait JsonConfigTrait
{
    public function __construct(null|string|array $data)
    {
        $this->setPropertiesFromJSON($data);
    }

    /**
     * @param \ReflectionClass<self> $reflectionClass
     * @throws \ReflectionException
     */
    private function propertyIsInt(\ReflectionClass $reflectionClass, string $key): bool
    {
        $propertyType = $reflectionClass->getProperty($key)->getType();

        return is_a($propertyType, \ReflectionNamedType::class) && $propertyType->getName() === 'int';
    }

    public static function decodeJson5(?string $json): ?array
    {
        if ($json === null) {
            return null;
        }
        $json    = str_replace("\r", "", $json);
        $json    = str_replace(chr(194) . chr(160), " ", $json);

        return \ColinODell\Json5\Json5Decoder::decode($json, true);
    }

    /**
     * @param \ReflectionClass<self> $reflectionClass
     * @throws \ReflectionException
     * @Ignore()
     */
    protected function setPropertyFromJson(string $key, mixed $val, \ReflectionClass $reflectionClass): void
    {
        $setterMethod = 'set' . ucfirst($key);
        if ($reflectionClass->hasMethod($setterMethod)) {
            $this->$setterMethod($val);
        } elseif (is_string($val) && $this->propertyIsInt($reflectionClass, $key)) {
            // Some database entries have stored integers as string, so let's try to typecast them
            if ($val === '' && $reflectionClass->getProperty($key)->getType()->allowsNull()) {
                $this->$key = null;
            } else {
                $this->$key = $val;
            }
        } elseif (is_null($val) && !$reflectionClass->getProperty($key)->getType()->allowsNull()) {
            // This might come from an earlier version, where the property of the JSON was accidentally null'ed.
            // In this case, we fall back to the default value - that is, we leave the property unchanged.
        } else {
            $this->$key = $val;
        }
    }

    /**
     * @throws ConfigurationError
     * @Ignore()
     */
    protected function setPropertiesFromJSON(null|string|array $data): void
    {
        if (!$data) {
            return;
        }
        if (is_array($data)) {
            $dataArr = $data;
        } else {
            $dataArr = self::decodeJson5($data);
        }
        if ($dataArr === null) {
            /** @var non-falsy-string $data */
            throw new ConfigurationError('Invalid JSON string: ' . $data);
        }

        try {
            $reflect = new \ReflectionClass(static::class);
            foreach ($dataArr as $key => $val) {
                if (property_exists($this, $key)) {
                    $this->setPropertyFromJson($key, $val, $reflect);
                }
            }
        } catch (\ReflectionException $e) {
            throw new ConfigurationError('Reflection error: ' . $e->getMessage());
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
