<?php
use app\models\db\Amendment;
use app\models\db\AmendmentSection;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 */

echo '<article class="ajaxAmendment">';

$link = Html::encode(\app\components\UrlHelper::createAmendmentUrl($amendment));
echo '<a href="' . $link . '" class="amendmentLink"><span class="glyphicon glyphicon-share"></span></a>';

if ($amendment->changeEditorial != '') {
    echo '<section id="section_editorial" class="motionTextHolder">';
    echo '<h3 class="green">' . \Yii::t('amend', 'editorial_hint') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeEditorial;
    echo '</div></div></section>';
}

/** @var AmendmentSection[] $sections */
$sections = $amendment->getSortedSections(true);
foreach ($sections as $section) {
    echo $section->getSectionType()->getAmendmentPlainHtml();
}

echo '</article>';
