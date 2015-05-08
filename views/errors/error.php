<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var string $name
 * @var string $message
 * @var int $httpStatus
 */

if (!isset($httpStatus)) {
    $httpStatus = 500;
}
if (!isset($name)) {
    $name = "Fehler";
}

switch ($httpStatus) {
    case 404:
        if ($message == "") {
            $message = "Die gesuchte Seite gibt es nicht.";
        }
        header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
        break;
    case 403:
        if ($message == "") {
            $message = "Kein Zugriff auf diese Seite.";
        }
        header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
        break;
    case 410:
        if ($message == "") {
            $message = "Dieser Inhalt wurde gelöscht.";
        }
        header($_SERVER["SERVER_PROTOCOL"] . ' 410 Gone');
        break;
    case 500:
        if ($message == "") {
            $message = "Ein interner Fehler ist aufgetreten.";
        }
        header($_SERVER["SERVER_PROTOCOL"] . ' 500 Internal Server Error');
        break;
}

$this->title = $name;
?>
    <h1><?= Html::encode($this->title) ?></h1>

    <br><br>

<div class="row">
    <div class="alert alert-danger col-md-10 col-md-offset-1">
        <?= $message ?>
    </div>
</div>
<br><br>