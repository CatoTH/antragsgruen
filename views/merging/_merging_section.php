<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\forms\MotionMergeAmendmentsInitForm $form
 * @var MotionSection $section
 */

use app\models\db\MotionSection;

echo '<h3 class="green">' . \yii\helpers\Html::encode($section->getSectionTitle()) . '</h3>';
echo '<div class="content sectionType' . \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE . '">';

$amendmentsById = [];
foreach ($section->getAmendingSections(true, false, true) as $sect) {
    $amendmentsById[$sect->amendmentId] = $sect->getAmendment();
}

$paragraphs = $section->getTextParagraphObjects(false, false, false);

foreach (array_keys($paragraphs) as $paragraphNo) {
    echo $this->render('_merging_paragraph', [
        'section'        => $section,
        'form'           => $form,
        'amendmentsById' => $amendmentsById,
        'paragraphNo'    => $paragraphNo,
    ]);
}

echo '</div>';
