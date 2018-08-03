<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 */

$initData = json_encode([
    'motions'    => \app\async\models\Motion::getCollection($consultation),
    'amendments' => \app\async\models\Amendment::getCollection($consultation),
]);
/** @var \app\models\settings\AntragsgruenApp $app */
$app = \Yii::$app->params;

$params = [
    'init-collections="' . htmlentities($initData, ENT_COMPAT, 'UTF-8') . '"'
];
if ($app->asyncConfig) {
    $params[] = 'cookie="' . htmlentities($_COOKIE['PHPSESSID'], ENT_COMPAT, 'UTF-8') . '"';
    $params[] = 'ws-port="' . IntVal($app->asyncConfig['port-external']) . '"';
}
?>
<h1>Async-Client</h1>

<div class="content">
    <app-root <?= implode(' ', $params) ?>></app-root>
    <script type="text/javascript" src="/angular/runtime.js"></script>
    <script type="text/javascript" src="/angular/polyfills.js"></script>
    <script type="text/javascript" src="/angular/vendor.js"></script>
    <script type="text/javascript" src="/angular/main.js"></script>
</div>
