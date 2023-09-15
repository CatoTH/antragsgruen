<?php

namespace app\models\db;

/**
 * @property string $cache
 * @method save($runValidation = true, $attributeNames = null)
 */
trait CacheTrait
{
    protected ?array $cacheObj = null;

    protected function getCacheObj(): array
    {
        if ($this->cacheObj === null) {
            if ($this->cache === null || $this->cache === '') {
                $this->cacheObj = [];
            } else {
                $this->cacheObj = unserialize($this->cache);
            }
        }

        return $this->cacheObj;
    }

    public function flushCache(bool $save = true): void
    {
        $this->cache    = '';
        $this->cacheObj = null;
        if ($save) {
            $this->save();
        }
    }

    public function flushCacheItems(array $items): void
    {
        $data    = $this->getCacheObj();
        $changed = false;

        // Delete all cache entries with a key that starts with any of the items;
        // item "motion" deletes cache keys like "motion", "motionSupporter"
        foreach (array_keys($data) as $existingKey) {
            foreach ($items as $toDeleteKey) {
                if (str_starts_with($existingKey, $toDeleteKey)) {
                    $changed = true;
                    unset($data[$existingKey]);
                }
            }
        }

        if ($changed) {
            $this->cacheObj = $data;
            $this->cache    = serialize($this->cacheObj);
            $this->save();
        }
    }

    public function getCacheItem(string $key): mixed
    {
        $data = $this->getCacheObj();
        if (!isset($data[$key])) {
            return null;
        }

        return $data[$key];
    }

    public function setCacheItem(string $key, mixed $value, bool $save = true): void
    {
        $data           = $this->getCacheObj();
        $data[$key]     = $value;
        $this->cacheObj = $data;
        $this->cache    = serialize($this->cacheObj);
        if ($save) {
            /*
            if (defined('YII_DEBUG') && YII_DEBUG) {
                return;
            }
            */
            $this->save();
        }
    }
}
