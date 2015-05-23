<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\User;
use app\models\forms\CommentForm;
use app\models\sectionTypes\ISectionType;
use app\views\motion\LayoutHelper as MotionLayoutHelper;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var bool $editLink
 * @var int[] $openedComments
 * @var string|null $adminEdit
 * @var null|string $supportStatus
 * @var null|CommentForm $commentForm
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;


$layout->addBreadcrumb($amendment->motion->getTypeName(), UrlHelper::createMotionUrl($amendment->motion));
$layout->addBreadcrumb($amendment->titlePrefix);

$this->title = $amendment->getTitle() . " (" . $amendment->motion->consultation->title . ", Antragsgrün)";


$html = '<ul class="sidebarActions">';

$html .= '</ul>';
$layout->menusHtml[] = $html;


echo '<h1>' . Html::encode($amendment->getTitle()) . '</h1>';


echo '<div class="motionData"><div class="content">';
echo '<table class="motionDataTable">
                <tr>
                    <th>' . Yii::t('amend', 'Antrag') . ':</th>
                    <td>' .
    Html::a($amendment->motion->title, UrlHelper::createMotionUrl($amendment->motion)) . '</td>
                </tr>
                <tr>
                    <th>' . Yii::t('amend', 'AntragsstellerIn'), ':</th>
                    <td>';

echo MotionLayoutHelper::formatInitiators($amendment->getInitiators(), $controller->consultation);

echo '</td></tr>
                <tr><th>Status:</th><td>';

$screeningMotionsShown = $amendment->motion->consultation->getSettings()->screeningMotionsShown;
$statiNames            = Amendment::getStati();
if ($amendment->status == Amendment::STATUS_SUBMITTED_UNSCREENED) {
    echo '<span class="unscreened">' . Html::encode($statiNames[$amendment->status]) . '</span>';
} elseif ($amendment->status == Amendment::STATUS_SUBMITTED_SCREENED && $screeningMotionsShown) {
    echo '<span class="screened">Von der Programmkommission geprüft</span>';
} else {
    echo Html::encode($statiNames[$amendment->status]);
}
if (trim($amendment->statusString) != "") {
    echo " <small>(" . Html::encode($amendment->statusString) . ")</string>";
}
echo '</td>
                </tr>';

if ($amendment->dateResolution != "") {
    echo '<tr><th>Entschieden am:</th>
       <td>' . Tools::formatMysqlDate($amendment->dateResolution) . '</td>
     </tr>';
}
echo '<tr><th>Eingereicht:</th>
       <td>' . Tools::formatMysqlDateTime($amendment->dateCreation) . '</td>
                </tr>';
echo '</table>';
echo '</div>';


foreach ($amendment->sections as $section) {
    if ($section->consultationSetting->type == ISectionType::TYPE_TITLE) {
        continue;
    }
    if ($section->consultationSetting->type == ISectionType::TYPE_TEXT_SIMPLE) {
        $formatter  = new \app\components\diff\AmendmentSectionFormatter($section);
        $diffGroups = $formatter->getInlineDiffGroupedLines();

        if (count($diffGroups) > 0) {
            echo '<section id="section_' . $section->sectionId . '" class="motionTextHolder">';
            echo '<h3 class="green">' . Html::encode($section->consultationSetting->title) . '</h3>';
            echo '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';

            foreach ($diffGroups as $diff) {
                echo '<section class="paragraph">';
                echo '<div class="text"><p>';
                if ($diff['lineFrom'] == $diff['lineTo']) {
                    echo 'In Zeile ' . $diff['lineFrom'] . ':<br>';
                } else {
                    echo 'Von Zeile ' . $diff['lineFrom'] . ' bis ' . $diff['lineTo'] . ':<br>';
                }
                echo $diff['text'];
                echo '</p></div></section>';
            }

            echo '</div>';
            echo '</section>';
        }
    }
}


if ($amendment->changeExplanation != '') {
    echo '<section id="amendmentExplanation" class="motionTextHolder">';
    echo '<h3 class="green">Begründung</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeExplanation;
    echo '</div></div>';
    echo '</section>';
}
