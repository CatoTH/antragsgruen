<?php

use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;
use yii\helpers\Html;

/**
 * @var \app\models\MotionSectionChanges $change
 */

if (!$change->hasChanges()) {
    echo '<div class="content noChanges">';
    echo \Yii::t('motion', 'diff_no_change');
    echo '</div>';
    return;
}

switch ($change->getSectionTypeId()) {
    case ISectionType::TYPE_TEXT_SIMPLE:
        $firstLine  = $change->getFirstLineNumber();
        $diffGroups = $change->getSimpleTextDiffGroups();
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
        echo \Yii::t('motion', 'diff_err_display');
        echo '</div>';
}
