<?php

namespace app\components\updater;

use yii\base\Application;
use yii\console\controllers\MigrateController;
use yii\db\Connection;
use yii\di\Instance;

class MigrateHelper extends MigrateController
{
    /**
     * MigrateHelper constructor.
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
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public static function getAvailableMigrations()
    {
        $controller = \Yii::createObject(static::class, ['migration', \Yii::$app]);
        return $controller->getNewMigrations();
    }
}
