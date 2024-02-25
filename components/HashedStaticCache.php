<?php

declare(strict_types=1);

namespace app\components;

use app\models\settings\AntragsgruenApp;

class HashedStaticCache
{
    private string $functionName;
    private ?array $dependencies;

    private bool $isBulky = false;
    private bool $skipCache = false;
    private ?int $timeout = null;

    public function __construct(string $functionName, ?array $dependencies)
    {
        $this->functionName = $functionName;
        $this->dependencies = $dependencies;

        if (YII_ENV === 'test') {
            $this->skipCache = true;
        }
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

    public function setIsBulky(bool $isBulky): self
    {
        $this->isBulky = $isBulky;

        return $this;
    }

    public function setTimeout(?int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    private function getCacheKey(): string
    {
        $dependencies = md5(print_r($this->dependencies, true));

        return md5($this->functionName . $dependencies);
    }

    public function getCached(callable $method): mixed
    {
        if (!$this->skipCache) {
            $cached = $this->getCache();
            if ($cached !== false) {
                return $cached;
            }
        }

        $result = $method();

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
        $key = $this->getCacheKey();

        if ($this->isBulky && AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $directory = self::getDirectory($key);
            if (file_exists($directory . '/' . $key)) {
                return (string)file_get_contents($directory . '/' . $key);
            } else {
                return false;
            }
        } else {
            return \Yii::$app->cache->get($this->getCacheKey());
        }
    }

    private function setCache(mixed $data): void
    {
        $key = $this->getCacheKey();

        if ($this->isBulky && AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $directory = self::getDirectory($key);
            if (!file_exists($directory)) {
                mkdir($directory, 0700);
            }

            file_put_contents($directory . '/' . $key, $data);
        } else {
            \Yii::$app->cache->set($key, $data, $this->timeout);
        }
    }

    public function flushCache(): void
    {
        $key = $this->getCacheKey();

        if ($this->isBulky && AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $directory = self::getDirectory($key);
            if (file_exists($directory . '/' . $key)) {
                unlink($directory . '/' . $key);
            }
        } else {
            \Yii::$app->cache->delete($key);
        }
    }
}
