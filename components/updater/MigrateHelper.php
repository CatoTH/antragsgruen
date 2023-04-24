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
        $this->db = Instance::ensure($this->db, Connection::class);
        foreach ($this->migrationPath as $i => $path) {
            $this->migrationPath[$i] = \Yii::getAlias($path);
        }
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public static function getAvailableMigrations(): array
    {
        $controller = \Yii::createObject(static::class, ['migration', \Yii::$app]);
        return $controller->getNewMigrations();
    }

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    public static function performMigrations(): void
    {
        $controller = \Yii::createObject(static::class, ['migration', \Yii::$app]);
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
        if ($conn && ($conn instanceof Connection || $conn instanceof \app\components\yii\DBConnection)) {
            $schema = $conn->getSchema();
            $schema->refresh();
        }

        $components = \Yii::$app->getComponents();

        foreach ($components as $name => $component) {
            if ($component instanceof CacheInterface) {
                \Yii::$app->get($name)->flush();
            } elseif (is_array($component) && isset($component['class']) && is_subclass_of($component['class'], 'yii\caching\CacheInterface')) {
                \Yii::$app->get($name)->flush();
            } elseif (is_string($component) && is_subclass_of($component, 'yii\caching\CacheInterface')) {
                \Yii::$app->get($name)->flush();
            } elseif ($component instanceof \Closure) {
                $cache = \Yii::$app->get($name);
                if (is_subclass_of($cache, 'yii\caching\CacheInterface')) {
                    \Yii::$app->get($name)->flush();
                }
            }
        }
    }
}
