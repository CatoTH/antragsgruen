<?php

namespace app\components\updater;

use yii\base\Application;
use yii\caching\CacheInterface;
use yii\console\controllers\MigrateController;
use yii\db\Connection;
use yii\di\Instance;

class MigrateHelper extends MigrateController
{
    /**
     * @param string $moduleId
     * @param Application $module
     * @param array $config
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($moduleId, $module, array $config = [])
    {
        parent::__construct($moduleId, $module, $config);
        /** @var Connection $connection */
        $connection = Instance::ensure($this->db, Connection::class);
        $this->db = $connection;

        /** @var string[] $paths */
        $paths = $this->migrationPath;
        foreach ($paths as $i => $path) {
            $this->migrationPath[$i] = \Yii::getAlias($path);
        }
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public static function getAvailableMigrations(): array
    {
        $controller = \Yii::createObject(static::class, ['migration', \Yii::$app]);
        /** @var MigrateHelper $controller */
        return $controller->getNewMigrations();
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public static function performMigrations(): void
    {
        $controller = \Yii::createObject(static::class, ['migration', \Yii::$app]);
        /** @var MigrateHelper $controller */
        $migrations = $controller->getNewMigrations();
        foreach ($migrations as $migration) {
            if (!$controller->migrateUp($migration)) {
                throw new \Exception('Migration failed: ' . $migration);
            }
        }
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\NotSupportedException
     */
    public static function flushCache(): void
    {
        $conn = \Yii::$app->get('db', false);
        if ($conn instanceof Connection) {
            $schema = $conn->getSchema();
            $schema->refresh();
        }

        $components = \Yii::$app->getComponents();

        foreach ($components as $name => $component) {
            if (
                $component instanceof CacheInterface ||
                is_array($component) && isset($component['class']) && is_subclass_of($component['class'], 'yii\caching\CacheInterface') ||
                is_string($component) && is_subclass_of($component, 'yii\caching\CacheInterface')
            ) {
                /** @var CacheInterface $cache */
                $cache = \Yii::$app->get($name);
                $cache->flush();
            } elseif ($component instanceof \Closure) {
                $cache = \Yii::$app->get($name);
                if (is_subclass_of($cache, 'yii\caching\CacheInterface')) {
                    /** @var CacheInterface $cache */
                    $cache->flush();
                }
            }
        }
    }
}
