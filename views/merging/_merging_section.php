<?php

/**
 * @var \yii\web\View $this
 * @var int[] $toMergeAmendmentIds
 * @var MotionSection $section
 */

use app\models\db\MotionSection;

$merger = $section->getAmendmentDiffMerger($toMergeAmendmentIds);

$amendmentsById = [];
foreach ($section->getAmendingSections(true, false, true) as $sect) {
    $amendmentsById[$sect->amendmentId] = $sect->getAmendment();
}

$paragraphs = $section->getTextParagraphObjects(false, false, false);

foreach (array_keys($paragraphs) as $paragraphNo) {
    echo $this->render('_merging_paragraph', [
        'section'             => $section,
        'toMergeAmendmentIds' => $toMergeAmendmentIds,
        'amendmentsById'      => $amendmentsById,
        'merger'              => $merger,
        'paragraphNo'         => $paragraphNo,
    ]);
}
