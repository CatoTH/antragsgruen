<?php

use app\models\db\User;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\SpeechQueue $queue
 */

if (!$queue) {
    return;
}

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$user       = User::getCurrentUser();

$layout->loadVue();

if ($user->hasPrivilege($controller->consultation, User::PRIVILEGE_SPEECH_QUEUES)) {
    echo $this->render('_index_speech_admin', ['queue' => $queue]);
}

echo $this->render('_index_speech_user', ['queue' => $queue]);
