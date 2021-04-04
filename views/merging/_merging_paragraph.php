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
?>
    <div class="leftToolbar">
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
<?php
$affectingAmendmentIds = $form->getAllAmendmentIdsAffectingParagraph($section, $paragraphNo, array_keys($amendmentsById));
if (count($affectingAmendmentIds) > 0) {
    ?>
    <section class="amendmentSelector">
        <h3 class="title">Absatz <?= ($paragraphNo + 1) ?>: Original</h3>
        <div class="original">
            <?php
            echo str_replace('###LINENUMBER###', '', implode('', $section->getTextParagraphLines()[$paragraphNo]));
            ?>
        </div>
        <h3 class="amend">Absatz <?= ($paragraphNo + 1) ?>: Änderungsausträge auswählen</h3>
        <ol>
        <?php
        foreach ($affectingAmendmentIds as $amendmentId) {
            $amendment = $amendmentsById[$amendmentId];
            $active = $form->isAmendmentActiveForParagraph($amendment->id, $section, $paragraphNo);
            ?>
            <li>
                <div class="selector">
                    <input type="checkbox" name="selectedAmendments[<?=$section->sectionId?>][<?=$paragraphNo?>][<?=$amendmentId?>]"
                           <?= ($active ? 'checked' : '') ?>
                           id="selectedAmendment_<?= $section->sectionId . '_' . $paragraphNo . '_' . $amendmentId ?>">
                </div>
                <label class="name" for="selectedAmendment_<?= $section->sectionId . '_' . $paragraphNo . '_' . $amendmentId ?>">
                    <?= Html::encode($amendment->titlePrefix) ?> (<?= Html::encode($amendment->getInitiatorsStr()) ?>)
                </label>
                <div class="text">
                    <?= $section->getAmendmentDiffMerger([$amendmentId])->getShortenedParagraphDiff($paragraphNo) ?>
                </div>
            </li>
            <?php
        }
        ?>
        </ol>
        <div class="selectActions">
            <div class="statusIndicator">
                <?php
                $collisions = $form->getParagraphCollidingAmendments($section, $paragraphNo);
                if (count($collisions) > 0) {
                    $strs = [];
                    foreach ($collisions as $collision) {
                        $strs[] = $amendmentsById[$collision[0]]->titlePrefix . ' kollidiert mit ' . $amendmentsById[$collision[1]]->titlePrefix;
                    }
                    echo '<div class="alert alert-danger"><p>Kollisionen: ' . implode(', ', $strs) . '. Wenn beide ausgewählt werden, muss der Konflikt anschließend von Hand aufgelöst werden.</p></div>';
                }
                ?>
            </div>
            <div class="submit">
                <button type="button" class="submitAccepted btn btn-primary">
                    Auswahl übernehmen
                </button>
            </div>
        </div>
    </section>
<?php } ?>
    <div class="form-group">
        <div class="wysiwyg-textarea" id="<?= $holderId ?>" data-fullHtml="0" data-unchanged="<?= Html::encode($draftParagraph->unchanged) ?>">
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
                echo \app\components\diff\amendmentMerger\ParagraphMerger::getFormattedCollision($paraData, $amendment, $amendmentsById);
            }
            ?>
        </div>
    </div>

<?php
echo '</section>';
