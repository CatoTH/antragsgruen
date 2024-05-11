<?php

declare(strict_types=1);

namespace app\components;

use app\models\settings\AntragsgruenApp;

class HashedStaticCache
{
    private string $functionName;
    private ?array $dependencies;
    private string $cacheKey;

    private bool $isBulky = false;
    private bool $isSynchronized = false;
    private bool $skipCache = false;
    private ?int $timeout = null;

    public function __construct(string $functionName, ?array $dependencies)
    {
        $this->functionName = $functionName;
        $this->dependencies = $dependencies;

        if (YII_ENV === 'test') {
            $this->skipCache = true;
        }

        $this->cacheKey = $this->calculateCacheKey();
    }

    public static function getInstance(string $functionName, ?array $dependencies): HashedStaticCache
    {
        return new self($functionName, $dependencies);
    }

    public function setSkipCache(bool $skipCache): self
    {
        $this->skipCache = $skipCache;

        return $this;
    }

    public function isSkipCache(): bool
    {
        return $this->skipCache;
    }

    public function setIsBulky(bool $isBulky): self
    {
        $this->isBulky = $isBulky;

        return $this;
    }

    /**
     * Setting a cache to synchronized prevents the system from generating the same cache in parallel.
     * This does come with some performance impact due to the communication with Redis / File system,
     * so it should only be set for larger operation.
     */
    public function setIsSynchronized(bool $isSynchronized): self
    {
        $this->isSynchronized = $isSynchronized;

        return $this;
    }

    public function setTimeout(?int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    private function calculateCacheKey(): string
    {
        $dependencies = md5(print_r($this->dependencies, true));

        return md5($this->functionName . $dependencies);
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    public function getCached(callable $method): mixed
    {
        if (!$this->skipCache) {
            // Hint: don't even try to aquire a lock if a cache item already exists
            $cached = $this->getCache();
            if ($cached !== false) {
                return $cached;
            }
        }

        if ($this->isSynchronized && !$this->skipCache) {
            ResourceLock::lockCacheForWrite($this);

            // Check if the cache item has been generated in the meantime
            $cached = $this->getCache();
            if ($cached !== false) {
                ResourceLock::unlockCache($this);
                return $cached;
            }

            $result = $method();
            ResourceLock::unlockCache($this);
        } else {
            $result = $method();
        }

        if (!$this->skipCache) {
            $this->setCache($result);
        }

        return $result;
    }

    private static function getDirectory(string $key): string {
        return AntragsgruenApp::getInstance()->viewCacheFilePath . '/' . substr($key, 0, 2);
    }

    /**
     * @return mixed|false
     */
    private function getCache(): mixed
    {
        if ($this->isBulky && AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $directory = self::getDirectory($this->cacheKey);
            if (file_exists($directory . '/' . $this->cacheKey)) {
                return (string)file_get_contents($directory . '/' . $this->cacheKey);
            } else {
                return false;
            }
        } else {
            return \Yii::$app->cache->get($this->cacheKey);
        }
    }

    public function cacheIsFilled(): bool
    {
        if ($this->isBulky && AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $directory = self::getDirectory($this->cacheKey);
            return file_exists($directory . '/' . $this->cacheKey);
        } else {
            return \Yii::$app->cache->exists($this->cacheKey);
        }
    }

    public function setCache(mixed $data): void
    {
        if ($this->isBulky && AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $directory = self::getDirectory($this->cacheKey);
            if (!file_exists($directory)) {
                mkdir($directory, 0700);
            }

            file_put_contents($directory . '/' . $this->cacheKey, $data);
        } else {
            \Yii::$app->cache->set($this->cacheKey, $data, $this->timeout);
        }
    }

    public function flushCache(): void
    {
        if ($this->isBulky && AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $directory = self::getDirectory($this->cacheKey);
            if (file_exists($directory . '/' . $this->cacheKey)) {
                unlink($directory . '/' . $this->cacheKey);
            }
        } else {
            \Yii::$app->cache->delete($this->cacheKey);
        }
    }
}
