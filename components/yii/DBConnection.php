<?php

namespace app\components\yii;

use app\models\settings\AntragsgruenApp;
use yii\web\View;

class DBConnection extends \yii\db\Connection
{
    private static bool $caughtError = false;

    /**
     * @throws \yii\base\ExitException
     */
    public function open(): void
    {
        try {
            parent::open();
        } catch (\yii\db\Exception $e) {
            if (\Yii::$app->controller instanceof \yii\console\Controller) {
                echo $e->getMessage() . "\n";
                echo $e->getTraceAsString();
                die();
            } elseif (self::$caughtError) {
                echo '<h1>Could not open database - and an endless loop occurred.</h1>';
                echo 'This should not happen under any circumstance. You might consider opening a ';
                echo '<a href="https://github.com/CatoTH/antragsgruen">bugreport</a>.';
            } else {
                $view = new View();
                echo \Yii::$app->controller->renderContent(
                    $view->render(
                        '@app/views/errors/error',
                        [
                            'name'       => 'Database connection error',
                            'message'    => 'An error ocurred when connecting to the database.',
                            'httpStatus' => 500,
                        ],
                        \Yii::$app->controller
                    )
                );
            }
            \Yii::$app->end(500);
        }
    }

    public static function executePlainQuery(string $sql): void
    {
        $sql = str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $sql);
        \Yii::$app->db->createCommand($sql)->execute();
    }

    public static function executePlainFetchArray(string $sql): array
    {
        $sql = str_replace('###TABLE_PREFIX###', AntragsgruenApp::getInstance()->tablePrefix, $sql);
        $command = \Yii::$app->db->createCommand($sql);
        return $command->queryAll(\PDO::FETCH_NUM);
    }
}
