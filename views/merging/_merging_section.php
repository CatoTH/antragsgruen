<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\mergeAmendments\Init $form
 * @var MotionSection $section
 * @var bool $twoCols
 */

use app\models\db\MotionSection;
use yii\helpers\Html;

$amendmentsById = [];
foreach ($section->getMergingAmendingSections(false, true) as $sect) {
    $amendmentsById[$sect->amendmentId] = $sect->getAmendment();
}

$paragraphs     = $section->getTextParagraphObjects(false, false, false, true);
$type           = $section->getSettings();
$lineNo         = $section->getFirstLineNumber();
$hasLineNumbers = $section->getSettings()->lineNumbers;
$paragraphNos   = array_keys($paragraphs);
$paragraphFirst = (count($paragraphNos) > 0 ? $paragraphNos[0] : null);
$paragraphLast  = (count($paragraphNos) > 0 ? $paragraphNos[count($paragraphNos) - 1] : null);

echo '<h3 class="green">' . Html::encode($section->getSectionTitle()) . '</h3>';
echo '<div class="content section section' . $section->sectionId . ' sectionType' . \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE . '">';

if (!$type->hasAmendments) {
    echo '<label class="removeSection">';
    echo Html::checkbox('removeSections[]', in_array($type->id, $form->draftData->removedSections), ['value' => $type->id]);
    echo Yii::t('amend', 'merge_remove_text');
    echo '</label>';
}

if ($twoCols) {
    echo '<div class="sectionHolder">';
} else {
    echo '<div class="sectionHolder boxed">';
}

$firstLineCount = $lineNo;
foreach ($paragraphNos as $paragraphNo) {
    $paragraph = $paragraphs[$paragraphNo];
    if ($twoCols) {
        echo '<div class="twoColsHolder">';
        echo '<div class="twoColsLeft sectionType' . $type->type . '">';
        echo '<div class="text motionTextFormattings textOrig';
        if ($section->getSettings()->fixedWidth) {
            echo ' fixedWidthFont';
        }
        echo '">';
        if ($section->getSettings()->fixedWidth || $hasLineNumbers) {
            foreach ($paragraph->lines as $i => $line) {
                if ($section->getSettings()->lineNumbers) {
                    /** @var int $lineNo */
                    $lineNoStr = '<span class="lineNumber" data-line-number="' . $lineNo++ . '" aria-hidden="true"></span>';
                    $line      = str_replace('###LINENUMBER###', $lineNoStr, $line);
                }
                $line   = str_replace('<br>', '', $line);
                $first3 = substr($line, 0, 3);
                if ($i > 0 && !in_array($first3, ['<ol', '<ul', '<p>', '<di'])) {
                    echo '<br>';
                }
                echo $line;
            }
        } else {
            echo $paragraph->origStr;
        }
        echo '</div>';

        echo '</div>';
        $add = '';
        if ($paragraphNo === $paragraphFirst) {
            $add .= ' first';
        }
        if ($paragraphNo === $paragraphLast) {
            $add .= ' last';
        }
        echo '<div class="twoColsRight sectionType' . $type->type . $add . '" data-section-id="' . $section->sectionId . '">';
    }

    $firstLineNo = $firstLineCount;
    $lastLineNo = $firstLineNo + count($paragraph->lines) - 1;
    $firstLineCount = $firstLineNo + count($paragraph->lines);

    echo $this->render('_merging_paragraph', [
        'section'        => $section,
        'form'           => $form,
        'amendmentsById' => $amendmentsById,
        'paragraphNo'    => $paragraphNo,
        'firstLineNo'    => $firstLineNo,
        'lastLineNo'     => $lastLineNo,
    ]);
    if ($twoCols) {
        echo '</div>';
        echo '</div>';
    }
}

echo '</div></div>';
