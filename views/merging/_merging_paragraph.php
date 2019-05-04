<?php
/**
 * @var MotionSection $section
 * @var int[] $toMergeAmendmentIds
 * @var Amendment[] $amendmentsById
 * @var \app\components\diff\amendmentMerger\ParagraphMerger $merger
 * @var int $paragraphNo
 */

use app\components\diff\DiffRenderer;
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;


$CHANGESET_COUNTER = 0;
$changeset = [];

$paragraphCollisions = $merger->getCollidingParagraphGroups($paragraphNo);

$out             = '';
$groupedParaData = $merger->getGroupedParagraphData($paragraphNo);
$paragraphText   = '';
foreach ($groupedParaData as $part) {
    $text = $part['text'];

    if ($part['amendment'] > 0) {
        $amendment = $amendmentsById[$part['amendment']];
        $cid       = $CHANGESET_COUNTER++;
        if (!isset($changeset[$amendment->id])) {
            $changeset[$amendment->id] = [];
        }
        $changeset[$amendment->id][] = $cid;

        $mid  = $cid . '-' . $amendment->id;
        $text = str_replace('###INS_START###', '###INS_START' . $mid . '###', $text);
        $text = str_replace('###DEL_START###', '###DEL_START' . $mid . '###', $text);
    }

    $paragraphText .= $text;
}

$out .= '<div class="paragraphHolder';
if (count($paragraphCollisions) > 0) {
    $out .= ' hasCollisions';
}
$out .= '" data-paragraph-no="' . $paragraphNo . '">';
$out .= DiffRenderer::renderForInlineDiff($paragraphText, $amendmentsById);

foreach ($paragraphCollisions as $amendmentId => $paraData) {
    $amendment    = $amendmentsById[$amendmentId];
    $amendmentUrl = UrlHelper::createAmendmentUrl($amendment);
    $out          .= '<div class="collidingParagraph"';
    $out          .= ' data-link="' . Html::encode($amendmentUrl) . '"';
    $out          .= ' data-username="' . Html::encode($amendment->getInitiatorsStr()) . '">';
    $out          .= '<p class="collidingParagraphHead"><strong>' . Yii::t('amend', 'merge_colliding');
    $out          .= ': ' . Html::a(Html::encode($amendment->titlePrefix), $amendmentUrl);
    $out          .= '</strong></p>';

    $paragraphText = '';

    foreach ($paraData as $part) {
        $text = $part['text'];

        if ($part['amendment'] > 0) {
            $amendment = $amendmentsById[$part['amendment']];
            $cid       = $CHANGESET_COUNTER++;
            if (!isset($changeset[$amendment->id])) {
                $changeset[$amendment->id] = [];
            }
            $changeset[$amendment->id][] = $cid;

            $mid  = $cid . '-' . $amendment->id;
            $text = str_replace('###INS_START###', '###INS_START' . $mid . '###', $text);
            $text = str_replace('###DEL_START###', '###DEL_START' . $mid . '###', $text);
        }

        $paragraphText .= $text;
    }

    $out .= DiffRenderer::renderForInlineDiff($paragraphText, $amendmentsById);
    $out .= '</div>';
}
$out .= '</div>';

echo $out;
