<?php

use app\components\diff\AmendmentSectionFormatter;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentSection;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var string $mode
 * @var \app\controllers\Base $controller
 * @var string|null $deleteDraftId
 */

$controller = $this->context;
$params     = $controller->layoutParams;

$this->title = Yii::t('amend', $mode == 'create' ? 'amendment_create' : 'amendment_edit');

$params->addBreadcrumb($amendment->motion->titlePrefix, UrlHelper::createMotionUrl($amendment->motion));
$params->addBreadcrumb('Änderungsantrag', UrlHelper::createAmendmentUrl($amendment, 'edit'));
$params->addBreadcrumb('Bestätigen');

echo '<h1>' . Yii::t('amend', 'confirm_amendment') . '</h1>';

if ($amendment->changeEditorial != '') {
    echo '<section id="section_editorial" class="motionTextHolder">';
    echo '<h3 class="green">' . 'Redaktionelle Änderung' . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeEditorial;
    echo '</div></div></section>';
}

/** @var AmendmentSection[] $sections */
$sections = $amendment->getSortedSections(false);
foreach ($sections as $section) {
    echo $section->getSectionType()->getAmendmentFormatted();
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
        <h3 class="green">Antragsteller_Innen</h3>

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

if ($deleteDraftId) {
    $controller->layoutParams->addOnLoadJS('localStorage.removeItem(' . json_encode($deleteDraftId) . ');');
}
