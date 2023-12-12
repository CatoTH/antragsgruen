<?php

use app\models\sectionTypes\{ISectionType, TextSimple};
use yii\helpers\Html;

/**
 * @var \app\models\MotionSectionChanges $change
 */

$diffGroups = ($change->getSectionTypeId() === ISectionType::TYPE_TEXT_SIMPLE ? $change->getSimpleTextDiffGroups() : null);

// This is mostly for non-changing re
if (is_array($diffGroups) && count($diffGroups) === 0 && !$change->getSectionType()->getSettings()->hasAmendments) {
    return;
}

echo '<section class="motionChangeView section' . $change->getSectionId() . '">';
echo '<h2 class="green">' . Html::encode($change->getSectionTitle()) . '</h2>';

if (!$change->hasChanges()) {
    echo '<div class="content noChanges">';
    echo Yii::t('motion', 'diff_no_change');
    echo '</div>';
    return;
}

switch ($change->getSectionTypeId()) {
    case ISectionType::TYPE_TITLE:
        echo '<div class="motionTextHolder"><div class="paragraph lineNumbers"><section class="paragraph"><div class="text motionTextFormattings">';
        echo '<strong>' . Yii::t('diff', 'change_from') . ':</strong><br>';
        echo Html::encode($change->oldSection->getData()) . "<br><br>";
        echo '<strong>' . Yii::t('diff', 'change_to') . ':</strong><br>' . Html::encode($change->newSection->getData()) . "<br><br>";
        echo '</div></section></div></div>';
        break;
    case ISectionType::TYPE_TEXT_SIMPLE:
        $firstLine  = $change->getFirstLineNumber();

        echo '<div class="motionTextHolder"><div class="paragraph lineNumbers">';

        $wrapStart = '<section class="paragraph"><div class="text motionTextFormattings';
        if ($change->isFixedWithFont()) {
            $wrapStart .= ' fixedWidthFont';
        }
        $wrapStart .= '">';
        $wrapEnd   = '</div></section>';
        echo TextSimple::formatDiffGroup($diffGroups, $wrapStart, $wrapEnd, $firstLine);

        echo '</div></div>';
        break;
    default:
        echo '<div class="content notDisplayable">';
        echo Yii::t('motion', 'diff_err_display');
        echo '</div>';
}

echo '</section>';
