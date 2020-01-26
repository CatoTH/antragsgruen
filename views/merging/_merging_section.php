<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\mergeAmendments\Init $form
 * @var MotionSection $section
 * @var bool $twoCols
 */

use app\models\db\MotionSection;

echo '<h3 class="green">' . \yii\helpers\Html::encode($section->getSectionTitle()) . '</h3>';
echo '<div class="content sectionType' . \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE . '">';
if ($twoCols) {
    echo '<div class="sectionHolder">';
} else {
    echo '<div class="sectionHolder boxed">';
}

$amendmentsById = [];
foreach ($section->getAmendingSections(true, false, true) as $sect) {
    $amendmentsById[$sect->amendmentId] = $sect->getAmendment();
}

$paragraphs     = $section->getTextParagraphObjects(false, false, false);
$type           = $section->getSettings();
$lineNo         = $section->getFirstLineNumber();
$hasLineNumbers = $section->getSettings()->lineNumbers;
$paragraphNos   = array_keys($paragraphs);
$paragraphFirst = $paragraphNos[0];
$paragraphLast  = $paragraphNos[count($paragraphNos) - 1];

foreach ($paragraphNos as $paragraphNo) {
    if ($twoCols) {
        $paragraph = $paragraphs[$paragraphNo];
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
                    $lineNoStr = '<span class="lineNumber" data-line-number="' . $lineNo++ . '"></span>';
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
    echo $this->render('_merging_paragraph', [
        'section'        => $section,
        'form'           => $form,
        'amendmentsById' => $amendmentsById,
        'paragraphNo'    => $paragraphNo,
    ]);
    if ($twoCols) {
        echo '</div>';
        echo '</div>';
    }
}

echo '</div></div>';
