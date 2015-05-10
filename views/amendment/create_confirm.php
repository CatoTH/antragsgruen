<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var string $mode
 * @var \app\controllers\Base $controller
 */

$controller = $this->context;
$params = $controller->layoutParams;

$this->title = Yii::t('amend', $mode == 'create' ? 'Änderungsantrag stellen' : 'Änderungsantrag bearbeiten');

$params->addBreadcrumb($amendment->motion->titlePrefix, UrlHelper::createMotionUrl($amendment->motion));
$params->addBreadcrumb('Änderungsantrag', UrlHelper::createAmendmentUrl($amendment, 'edit'));
$params->addBreadcrumb('Bestätigen');

echo '<h1>' . Yii::t('amend', 'Änderungsantrag bestätigen') . '</h1>';

foreach ($amendment->getSortedSections(true) as $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }
    echo '<section class="motionTextHolder">';
    echo '<h2 class="green">' . Html::encode($section->consultationSetting->title) . '</h2>';
    echo '<div class="textholder consolidated">';

    echo $section->getSectionType()->showSimple();

    echo '</div>';
    echo '</section>';
}


if ($amendment->changeExplanation != '') {
    echo '<div class="motionTextHolder amendmentReasonHolder">';
    echo '<h3 class="green">Begründung des Änderungsantrags</h3>';
    echo '<div class="content">';
    echo $amendment->changeExplanation;
    echo '</div>';
    echo '</div>';
}


echo '<div class="motionTextHolder">
        <h3 class="green">AntragstellerInnen</h3>

        <div class="content">
            <ul>';

foreach ($amendment->getInitiators() as $unt) {
    echo '<li style="font-weight: bold;">' . $unt->getNameWithResolutionDate(true) . '</li>';
}

foreach ($amendment->getSupporters() as $unt) {
    echo '<li>' . $unt->getNameWithResolutionDate(true) . '</li>';
}
echo '
            </ul>
        </div>
    </div>';

echo Html::beginForm('', 'post', ['id' => 'amendmentConfirmForm']);

echo '<div class="content">
        <div style="float: right;">
            <button type="submit" name="confirm" class="btn btn-success">
                <span class="glyphicon glyphicon-ok-sign"></span> Einreichen
            </button>
        </div>
        <div style="float: left;">
            <button type="submit" name="modify" class="btn">
                <span class="glyphicon glyphicon-remove-sign"></span> Korrigieren
            </button>
        </div>
    </div>';

echo Html::endForm();
