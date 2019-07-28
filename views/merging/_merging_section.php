<?php

/**
 * @var \yii\web\View $this
 * @var int[] $toMergeMainIds
 * @var int[] $toMergeResolvedIds
 * @var array $amendmentVersions
 * @var MotionSection $section
 */

use app\models\db\MotionSection;

$merger = $section->getAmendmentDiffMerger($toMergeResolvedIds);
$mergerAll = $section->getAmendmentDiffMerger(null);

echo '<h3 class="green">' . \yii\helpers\Html::encode($section->getSectionTitle()) . '</h3>';
echo '<div class="content">';

$amendmentsById = [];
foreach ($section->getAmendingSections(true, false, true) as $sect) {
    $amendmentsById[$sect->amendmentId] = $sect->getAmendment();
}

$paragraphs = $section->getTextParagraphObjects(false, false, false);

foreach (array_keys($paragraphs) as $paragraphNo) {
    echo $this->render('_merging_paragraph', [
        'section'             => $section,
        'toMergeMainIds'      => $toMergeMainIds,
        'amendmentsById'      => $amendmentsById,
        'merger'              => $merger,
        'mergerAll'           => $mergerAll,
        'paragraphNo'         => $paragraphNo,
    ]);
}

echo '</div>';
