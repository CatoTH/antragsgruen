<?php

use app\components\UrlHelper;
use app\models\db\{Amendment, MotionSection};
use app\models\mergeAmendments\Init;
use yii\helpers\Html;

/**
 * @var MotionSection $section
 * @var Init $form
 * @var Amendment[] $amendmentsById
 * @var int $paragraphNo
 * @var int $firstLineNo
 * @var int $lastLineNo
 */

$draftParagraph      = $form->draftData->paragraphs[$section->sectionId . '_' . $paragraphNo];
$paragraphCollisions = array_filter(
    $form->getParagraphTextCollisions($section, $paragraphNo),
    function ($amendmentId) use ($draftParagraph) {
        return !in_array($amendmentId, $draftParagraph->handledCollisions);
    },
    ARRAY_FILTER_USE_KEY
);

$type         = $section->getSettings();
$nameBase     = 'sections[' . $type->id . '][' . $paragraphNo . ']';
$htmlId       = 'sections_' . $type->id . '_' . $paragraphNo;
$holderId     = 'section_holder_' . $type->id . '_' . $paragraphNo;
$reloadUrl    = UrlHelper::createMotionUrl($section->getMotion(), 'merge-amendments-paragraph-ajax', [
    'sectionId'   => $type->id,
    'paragraphNo' => $paragraphNo,
    'amendments'  => 'DUMMY',
]);
$jsStatusData = $form->getJsParagraphStatusData($section, $paragraphNo, $amendmentsById);

echo '<section class="paragraphWrapper ' . (count($paragraphCollisions) > 0 ? ' hasCollisions' : '') .
     '" data-section-id="' . $type->id . '" data-paragraph-id="' . $paragraphNo . '" ' .
     'id="paragraphWrapper_' . $type->id . '_' . $paragraphNo . '" ' .
     'data-reload-url="' . Html::encode($reloadUrl) . '">';
$linesAria = str_replace(['%FROM%', '%TO%'], [$firstLineNo, $lastLineNo], Yii::t('amend', 'merge_line_range_aria'));
?>
    <div class="leftToolbar">
        <div class="lineNumbers" aria-label="<?= Html::encode($linesAria) ?>">
            <?php
            if ($firstLineNo <= $lastLineNo) {
                // $lastLineNo < $firstLineNo happens for sections that only have one completely empty paragraph
                ?>
                <div class="firstLineNumber" aria-hidden="true"><?= $firstLineNo ?></div>
                <?php

            }
            if ($lastLineNo > $firstLineNo + 1) { ?>
                <div class="inbetweenLineNumbers" aria-hidden="true"></div>
                <?php
            }
            if ($lastLineNo > $firstLineNo) { ?>
                <div class="lastLineNumber" aria-hidden="true"><?= $lastLineNo ?></div>
                <?php
            }
            ?>
        </div>
        <div class="changedIndicator unchanged"><span
                class="glyphicon glyphicon-edit" title="<?= Yii::t('amend', 'merge_changed') ?>"
                aria-label="<?= Yii::t('amend', 'merge_changed') ?>"></span></div>
    </div>
    <div class="changeToolbar">
        <div class="statuses" data-amendments="<?= Html::encode(json_encode($jsStatusData)) ?>"></div>
        <div class="actions">
            <div class="mergeActionHolder hidden">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-default dropdown-toggle dropdownAll" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        <?= Yii::t('amend', 'merge_all') ?>
                        <span class="caret" aria-hidden="true"></span>
                        <span class="sr-only"><?= Yii::t('base', 'toggle_dropdown') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li><a href="#" class="acceptAll"><?= Yii::t('amend', 'merge_accept_all') ?></a></li>
                        <li><a href="#" class="rejectAll"><?= Yii::t('amend', 'merge_reject_all') ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group">
        <div class="wysiwyg-textarea" id="<?= $holderId ?>" data-fullHtml="0" data-unchanged="<?= Html::encode($draftParagraph->unchanged ?: '') ?>">
            <!--suppress HtmlFormInputWithoutLabel -->
            <textarea name="<?= $nameBase ?>[raw]" class="raw" id="<?= $htmlId ?>"
                      title="<?= Html::encode($type->title) ?>"></textarea>
            <!--suppress HtmlFormInputWithoutLabel -->
            <textarea name="<?= $nameBase ?>[consolidated]" class="consolidated"
                      title="<?= Html::encode($type->title) ?>"></textarea>
            <div class="texteditor motionTextFormattings ICE-Tracking<?php
            if ($section->getSettings()->fixedWidth) {
                echo ' fixedWidthFont';
            }
            ?>" data-allow-diff-formattings="1" data-autocolorize="1" id="<?= $htmlId ?>_wysiwyg" title="">
                <?= $draftParagraph->text ?>
            </div>
        </div>
        <div class="collisionsHolder">
            <?php
            foreach ($paragraphCollisions as $amendmentId => $paraData) {
                $amendment = $amendmentsById[$amendmentId];
                echo \app\components\diff\amendmentMerger\ParagraphMerger::getFormattedCollision($paraData, $amendment, $amendmentsById, true);
            }
            ?>
        </div>
    </div>

<?php
echo '</section>';
