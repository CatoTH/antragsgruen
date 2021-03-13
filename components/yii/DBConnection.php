<?php

namespace app\components\yii;

use yii\web\View;

class DBConnection extends \yii\db\Connection
{
    private static $catchedError = false;

    /**
     * @throws \yii\base\ExitException
     */
    public function open()
    {
        try {
            parent::open();
        } catch (\yii\db\Exception $e) {
            if (static::$catchedError) {
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
}
