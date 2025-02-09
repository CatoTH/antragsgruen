<?php

declare(strict_types=1);

namespace app\components;

use app\models\backgroundJobs\BuildStaticCache;
use app\models\settings\AntragsgruenApp;

class HashedStaticCache
{
    private const BACKGROUND_JOB_CACHE_MAX_AGE = 3;

    private string $functionName;
    private ?array $dependencies;
    private string $cacheKey;

    private bool $isBulky = false;
    private bool $isSynchronized = false;
    private bool $skipCache = false;
    private ?int $timeout = null;

    private ?BuildStaticCache $rebuildBackgroundJob = null;

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

    public function setRebuildBackgroundJob(BuildStaticCache $rebuildBackgroundJob): self
    {
        $this->rebuildBackgroundJob = $rebuildBackgroundJob;

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

    public function getCached(?callable $method, ?callable $cacheOutdatedDecorator = null): mixed
    {
        if (!$this->skipCache) {
            // Hint: don't even try to aquire a lock if a cache item already exists
            $cached = $this->getCache();
            if ($cached !== false) {
                $this->triggerBackgroundJobCacheRebuildIfNecessary($cached);

                return $this->returnDecoratedCache($cached, $cacheOutdatedDecorator);
            }
        }

        if ($this->isSynchronized && !$this->skipCache) {
            ResourceLock::lockCacheForWrite($this);

            // Check if the cache item has been generated in the meantime
            $cached = $this->getCache();
            if ($cached !== false) {
                ResourceLock::unlockCache($this);
                $this->triggerBackgroundJobCacheRebuildIfNecessary($cached);
                return $this->returnDecoratedCache($cached, $cacheOutdatedDecorator);
            }

            if ($method) {
                $result = $method();
            } elseif ($this->rebuildBackgroundJob) {
                $this->rebuildBackgroundJob->execute();

                $result = $this->rebuildBackgroundJob->getResult();
            } else {
                throw new \Exception('Either a callback or a background job needs to be provided');
            }

            ResourceLock::unlockCache($this);
        } else {
            if ($method) {
                $result = $method();
            } elseif ($this->rebuildBackgroundJob) {
                $this->rebuildBackgroundJob->execute();

                $result = $this->rebuildBackgroundJob->getResult();
            } else {
                throw new \Exception('Either a callback or a background job needs to be provided');
            }
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
     * @param array{content: mixed, createdAtTs: int|null} $cache
     */
    private function returnDecoratedCache(array $cache, ?callable $cacheOutdatedDecorator): mixed
    {
        $content = $cache['content'];
        if ($cacheOutdatedDecorator) {
            $content = $cacheOutdatedDecorator($cache['content'], $cache['createdAtTs']);
        }
        return $content;
    }

    /**
     * @return array{content: mixed, createdAtTs: int|null}|false
     */
    private function getCache(): array|false
    {
        if ($this->isBulky && AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $directory = self::getDirectory($this->cacheKey);
            if (file_exists($directory . '/' . $this->cacheKey)) {
                $mtime = filemtime($directory . '/' . $this->cacheKey);
                return [
                    'content' => (string)file_get_contents($directory . '/' . $this->cacheKey),
                    'createdAtTs' => $mtime ?: null,
                ];
            } else {
                return false;
            }
        } else {
            $content = \Yii::$app->cache->get($this->cacheKey);
            if ($content === false) {
                return false;
            } else {
                return [
                    'content' => $content,
                    'createdAtTs' => null,
                ];
            }
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
        if ($this->rebuildBackgroundJob) {
            // Leave the cache untouched. It will be regenerated by the background job.
            $this->triggerBackgroundJobCacheRebuild();
            return;
        }

        if ($this->isBulky && AntragsgruenApp::getInstance()->viewCacheFilePath) {
            $directory = self::getDirectory($this->cacheKey);
            if (file_exists($directory . '/' . $this->cacheKey)) {
                unlink($directory . '/' . $this->cacheKey);
            }
        } else {
            \Yii::$app->cache->delete($this->cacheKey);
        }
    }

    public function triggerBackgroundJobCacheRebuild(): void
    {
        BackgroundJobScheduler::executeOrScheduleJob($this->rebuildBackgroundJob);
    }

    /**
     * @param array{content: mixed, createdAtTs: int|null} $data
     */
    public function triggerBackgroundJobCacheRebuildIfNecessary(array $data): void
    {
        if (!$this->rebuildBackgroundJob) {
            return;
        }

        if ($data['createdAtTs'] !== null && $data['createdAtTs'] < time() - self::BACKGROUND_JOB_CACHE_MAX_AGE) {
            $this->triggerBackgroundJobCacheRebuild();
            // @TODO Test
        }
    }
}
