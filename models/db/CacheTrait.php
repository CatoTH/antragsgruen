<?php

namespace app\models\db;

/**
 * @property string $cache
 * @method save($runValidation = true, $attributeNames = null)
 */
trait CacheTrait
{
    /** @var null|array */
    protected $cacheObj = null;

    /**
     * @return array
     */
    protected function getCacheObj()
    {
        if ($this->cacheObj === null) {
            if ($this->cache === null || $this->cache == '') {
                $this->cacheObj = [];
            } else {
                $this->cacheObj = json_decode($this->cache, true);
            }
        }
        return $this->cacheObj;
    }

    /**
     * @param bool $save
     */
    public function flushCache($save = true)
    {
        $this->cache    = '';
        $this->cacheObj = null;
        if ($save) {
            $this->save();
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getCacheItem($key)
    {
        $data = $this->getCacheObj();
        if (!isset($data[$key])) {
            return null;
        }
        return $data[$key];
    }

    /**
     * @param string $key
     * @param string $value
     * @param bool $save
     */
    public function setCacheItem($key, $value, $save = true)
    {
        $data = $this->getCacheObj();
        $data[$key] = $value;
        $this->cacheObj = $data;
        $this->cache = json_encode($this->cacheObj);
        if ($save) {
            $this->save();
        }
    }
}
