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
 */

$controller = $this->context;
$params     = $controller->layoutParams;

$this->title = Yii::t('amend', $mode == 'create' ? 'Änderungsantrag stellen' : 'Änderungsantrag bearbeiten');

$params->addBreadcrumb($amendment->motion->titlePrefix, UrlHelper::createMotionUrl($amendment->motion));
$params->addBreadcrumb('Änderungsantrag', UrlHelper::createAmendmentUrl($amendment, 'edit'));
$params->addBreadcrumb('Bestätigen');

echo '<h1>' . Yii::t('amend', 'Änderungsantrag bestätigen') . '</h1>';


/** @var AmendmentSection[] $sections */
$sections = $amendment->getSortedSections(false);
foreach ($sections as $section) {
    if ($section->consultationSetting->type == ISectionType::TYPE_TEXT_SIMPLE) {
        $formatter  = new AmendmentSectionFormatter($section, \app\components\diff\Diff::FORMATTING_CLASSES);
        $diffGroups = $formatter->getGroupedDiffLinesWithNumbers();

        if (count($diffGroups) > 0) {
            echo '<section id="section_' . $section->sectionId . '" class="motionTextHolder">';
            echo '<h3 class="green">' . Html::encode($section->consultationSetting->title) . '</h3>';
            echo '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';
            $wrapStart = '<section class="paragraph"><div class="text">';
            $wrapEnd   = '</section>';
            $firstLine = $section->getFirstLineNumber();
            $html      = TextSimple::formatDiffGroup($diffGroups, $wrapStart, $wrapEnd, $firstLine);
            echo str_replace('###FORCELINEBREAK###', '<br>', $html);
            echo '</div>';
            echo '</section>';
        }
    } elseif ($section->consultationSetting->type == ISectionType::TYPE_TITLE) {
        if ($section->data == $section->getOriginalMotionSection()->data) {
            continue;
        }
        echo '<section id="section_title" class="motionTextHolder">';
        echo '<h3 class="green">' . Html::encode($section->consultationSetting->title) . '</h3>';
        echo '<div id="section_title_0" class="paragraph"><div class="text">';
        echo '<h4 class="lineSummary">' . 'Ändern in' . ':</h4>';
        echo '<p>' . Html::encode($section->data) . '</p>';
        echo '</div></div></section>';
    }
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
