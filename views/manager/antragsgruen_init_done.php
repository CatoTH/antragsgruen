<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\forms\AntragsgruenInitSite $form
 * @var string $delInstallFileCmd
 * @var bool $installFileDeletable
 */


$controller  = $this->context;
$this->title = 'Antragsgrün installieren';

/** @var \app\controllers\admin\IndexController $controller */
$controller            = $this->context;
$layout                = $controller->layoutParams;
$layout->robotsNoindex = true;


echo '<h1>' . 'Antragsgrün installieren' . '</h1>';
$settingsUrl = UrlHelper::createUrl('manager/siteconfig');
echo Html::beginForm($settingsUrl, 'get', ['class' => 'antragsgruenInitForm form-horizontal']);

echo '<div class="content">';
echo $controller->showErrors();

$link = UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index'));
$link = '<br>' . Html::a($link, $link) . '<br><br>';

if ($installFileDeletable) {
    echo '<div class="saveholder">';
    echo '<button class="btn btn-success" name="finishInit">';
    echo \yii::t('manager', 'finish_install');
    echo '</button></div>';
} else {
    echo '<div class="alert alert-info" role="alert">';
    echo str_replace('%DELCMD%', Html::encode($delInstallFileCmd), 'Um den Installationsmodus zu beenden,
                lösche die Datei config/INSTALLING.
                Je nach Betriebssystem könnte der Befehl dazu z.B. folgendermaßen lauten:<pre>%DELCMD%</pre>
                Rufe danach diese Seite hier neu auf.
                ');
    echo '</div>';
}


echo '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">Success:</span>
                ' . str_replace('%LINK%', $link, 'Alles klar! Du kannst nun im Folgenden noch ein
                paar Detaileinstellungen vornehmen.
                Die Antragsgrün-Version ist nun unter folgender Adresse erreichbar: %LINK%') . '
            </div>';

echo '<div class="saveholder">';
echo '<button class="btn btn-success" name="finishInit">';
echo 'Detaileinstellungen';
echo '</button></div>';


echo '</div>';
echo Html::endForm();
