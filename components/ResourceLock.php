<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, IVotingItem, Motion, VotingBlock, VotingQuestion};
use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use Symfony\Component\Lock\{LockFactory,
    LockInterface,
    SharedLockInterface,
    Store\RedisStore,
    Store\SemaphoreStore};

final class ResourceLock
{
    /** @var LockInterface[] */
    private static array $acquiredLocks = [];
    private static ?LockFactory $lockFactory = null;

    private static function createRedisStore(AntragsgruenApp $app): RedisStore
    {
        $uri = 'tcp://' . $app->redis['hostname'] . ':' . $app->redis['port'];
        $options = [
            'parameters' => [
                'database' => $app->redis['database'],
            ]
        ];
        if (!empty($app->redis['password'])) {
            $options['parameters']['password'] = $app->redis['password'];
        }
        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedNamespaceInspection */
        $redis = new \Predis\Client($uri, $options);

        /** @noinspection PhpParamsInspection */
        return new RedisStore($redis);
    }

    private static function getLockFactory(): LockFactory {
        if (static::$lockFactory === null) {
            $app = AntragsgruenApp::getInstance();
            if ($app->redis) {
                $store = static::createRedisStore($app);
            } else {
                $store = new SemaphoreStore();
            }
            static::$lockFactory = new LockFactory($store);
        }
        return static::$lockFactory;
    }

    private static function acquireWriteLock(string $resourceId): void {
        $lock = static::getLockFactory()->createLock($resourceId, 15);
        $lock->acquire(true);
        static::$acquiredLocks[$resourceId] = $lock;
    }

    private static function acquireReadLock(string $resourceId): void {
        $lock = static::getLockFactory()->createLock($resourceId, 15);
        if ($lock instanceof SharedLockInterface) {
            $lock->acquireRead(true);
        } else {
            $lock->acquire(true);
        }
        static::$acquiredLocks[$resourceId] = $lock;
    }

    /**
     * @param string[] $resourceIds
     */
    public static function lockResourcesForWrite(array $resourceIds): void
    {
        sort($resourceIds);
        foreach ($resourceIds as $resourceId) {
            static::acquireWriteLock($resourceId);
        }
    }

    /**
     * @param string[] $resourceIds
     */
    public static function unlockResources(array $resourceIds): void
    {
        sort($resourceIds);
        foreach ($resourceIds as $resourceId) {
            if (isset(static::$acquiredLocks[$resourceId])) {
                static::$acquiredLocks[$resourceId]->release();
                unset(static::$acquiredLocks[$resourceId]);
            } else {
                throw new Internal('There was no lock to release');
            }
        }
    }

    public static function releaseAllLocks(): void
    {
        foreach (static::$acquiredLocks as $lock) {
            $lock->release();
        }
        static::$acquiredLocks = [];
    }


    public static function lockCacheForWrite(HashedStaticCache $cache): void
    {
        ResourceLock::acquireWriteLock('cache.' . $cache->getCacheKey());
    }

    public static function unlockCache(HashedStaticCache $cache): void
    {
        ResourceLock::unlockResources(['cache.' . $cache->getCacheKey()]);
    }

    public static function lockVotingBlockForRead(VotingBlock $votingBlock): void
    {
        ResourceLock::acquireReadLock('voting.' . $votingBlock->id);
    }

    public static function lockVotingBlockForWrite(VotingBlock $votingBlock): void
    {
        ResourceLock::acquireWriteLock('voting.' . $votingBlock->id);
    }

    private static function getVotingItemLockId(IVotingItem $item): string
    {
        if (is_a($item, Motion::class)) {
            return 'voting.motion.' . $item->id;
        } elseif (is_a($item, Amendment::class)) {
            return 'voting.amendment.' . $item->id;
        } else {
            /** @var VotingQuestion $item */
            return 'voting.question.' . $item->id;
        }
    }

    public static function lockVotingItemForVoting(IVotingItem $item): void
    {
        ResourceLock::acquireWriteLock(static::getVotingItemLockId($item));
    }

    public static function unlockVotingItemForVoting(IVotingItem $item): void
    {
        ResourceLock::unlockResources([static::getVotingItemLockId($item)]);
    }

    public static function lockVotingBlockItemGroup(VotingBlock $votingBlock, string $itemGroup): void
    {
        $resourceIds = array_map(function (IVotingItem $imotion): string {
            return static::getVotingItemLockId($imotion);
        }, $votingBlock->getItemGroupItems($itemGroup));
        static::lockResourcesForWrite($resourceIds);
    }

    public static function unlockVotingBlockItemGroup(VotingBlock $votingBlock, string $itemGroup): void
    {
        $resourceIds = array_map(function (IVotingItem $imotion): string {
            return static::getVotingItemLockId($imotion);
        }, $votingBlock->getItemGroupItems($itemGroup));
        static::unlockResources($resourceIds);
    }
}
