<?php

/**
 * @var yii\web\View $this
 */

/** @var \app\controllers\admin\IndexController $controller */
$controller            = $this->context;
$layout                = $controller->layoutParams;
$layout->robotsNoindex = true;
$this->title           = 'Kein Zugriff';

echo '<h1>Kein Zugriff</h1>

<div class="content">
    Dein Zugang ist für diese Seite nicht freigeschaltet. Falls du meinst, dass das ein Fehler ist,
    wende dich bitte an die AdministratorInnen dieser Seite (Impressum).
</div>
';
