<?php

namespace app\components\yii;


/**
 * @extends \yii\web\Application<\app\models\db\User>
 */
class Application extends \yii\web\Application
{
    /**
     * @inheritdoc
     */
    public function coreComponents(): array
    {
        return array_merge(parent::coreComponents(), [
            'user'         => ['class' => User::class],
        ]);
    }
}
