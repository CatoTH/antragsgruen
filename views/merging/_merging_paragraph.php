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

$type      = $section->getSettings();
$nameBase  = 'sections[' . $type->id . '][' . $paragraphNo . ']';
$htmlId    = 'sections_' . $type->id . '_' . $paragraphNo;
$holderId  = 'section_holder_' . $type->id . '_' . $paragraphNo;
$reloadUrl = UrlHelper::createMotionUrl($section->getMotion(), 'merge-amendments-paragraph-ajax', [
    'sectionId'   => $type->id,
    'paragraphNo' => $paragraphNo,
    'amendments'  => 'DUMMY',
]);

echo '<section class="paragraphWrapper ' . (count($paragraphCollisions) > 0 ? ' hasCollisions' : '') .
     '" data-section-id="' . $type->id . '" data-paragraph-id="' . $paragraphNo . '" ' .
     'id="paragraphWrapper_' . $type->id . '_' . $paragraphNo . '" ' .
     'data-reload-url="' . Html::encode($reloadUrl) . '">';


$allAmendingIds = $form->getAllAmendmentIdsAffectingParagraph($section, $paragraphNo);
if (count($allAmendingIds) > 0) {
    ?>
    <div class="leftToolbar">
        <div class="changedIndicator unchanged"><span class="glyphicon glyphicon-edit" title="<?= Yii::t('amend', 'merge_changed') ?>"></span></div>
    </div>
    <div class="changeToolbar">
        <div class="statuses">
            <?php
            list($normalAmendments, $modUs) = $form->getAffectingAmendmentsForParagraph($allAmendingIds, $amendmentsById, $paragraphNo);
            foreach ($normalAmendments as $amendment) {
                /** @var Amendment $amendment */
                $active       = $form->isAmendmentActiveForParagraph($amendment->id, $section, $paragraphNo);
                $amendmentUrl = UrlHelper::createAmendmentUrl($amendment);

                $statuses                     = [
                    Amendment::STATUS_PROCESSED         => Yii::t('structure', 'STATUS_PROCESSED'),
                    Amendment::STATUS_ACCEPTED          => Yii::t('structure', 'STATUS_ACCEPTED'),
                    Amendment::STATUS_REJECTED          => Yii::t('structure', 'STATUS_REJECTED'),
                    Amendment::STATUS_MODIFIED_ACCEPTED => Yii::t('structure', 'STATUS_MODIFIED_ACCEPTED'),
                ];
                $statusesAll                  = $amendment->getStatusNames();
                $statuses[$amendment->status] = Yii::t('amend', 'merge_status_unchanged') . ': ' .
                                                $statusesAll[$amendment->status];

                $classes   = ['btn', 'btn-sm', 'toggleAmendment'];
                $classes[] = ($active ? 'toggleActive' : 'btn-default');
                $classes[] = 'toggleAmendment' . $amendment->id;
                $idadd     = $type->id . '_' . $paragraphNo . '_' . $amendment->id;
                ?>
                <div class="btn-group amendmentStatus amendmentStatus<?= $amendment->id ?>" data-amendment-id="<?= $amendment->id ?>">
                    <button class="btn <?= ($active ? 'toggleActive' : 'btn-default') ?> btn-sm dropdown-toggle dropdownAmendment"
                            type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <button type="button" class="<?= implode(" ", $classes) ?>">
                        <input name="<?= $nameBase ?>[<?= $amendment->id ?>]" value="<?= ($active ? '1' : '0') ?>"
                               type="hidden" class="amendmentActive" data-amendment-id="<?= $amendment->id ?>">
                        <?= ($amendment->titlePrefix ? Html::encode($amendment->titlePrefix) : '-') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right">
                        <li>
                            <a href="<?= Html::encode($amendmentUrl) ?>" class="amendmentLink" target="_blank">
                                <span class="glyphicon glyphicon-new-window"></span>
                                <?= Yii::t('amend', 'merge_amend_show') ?>
                            </a>
                        </li>
                        <?php
                        if ($amendment->getMyProposalReference()) {
                            ?>
                            <li role="separator" class="divider"></li>
                            <li class="versionorig">
                                <a href="#" class="setVersion" data-version="<?= Init::TEXT_VERSION_ORIGINAL ?>">
                                    <?= Yii::t('amend', 'merge_amtable_text_orig') ?>
                                </a>
                            </li>
                            <li class="versionprop">
                                <a href="#" class="setVersion" data-version="<?= Init::TEXT_VERSION_PROPOSAL ?>">
                                    <?= Yii::t('amend', 'merge_amtable_text_prop') ?>
                                </a>
                            </li>
                            <?php
                        }
                        ?>
                        <li role="separator" class="divider dividerLabeled" data-label="<?= Html::encode(Yii::t('amend', 'merge_status_set')) ?>:"></li>
                        <?php
                        foreach ($statuses as $statusId => $statusName) {
                            echo '<li class="status' . $statusId . '">' .
                                 '<a href="" class="setStatus" data-status="' . $statusId . '">' .
                                 Html::encode($statusName) . '</a></li>';
                        }
                        ?>
                        <li role="separator" class="divider dividerLabeled" data-label="<?= Html::encode(Yii::t('amend', 'merge_voting_set')) ?>:"></li>
                        <li>
                            <div class="votingResult">
                                <label for="votesComment<?= $idadd ?>"><?= Yii::t('amend', 'merge_new_votes_comment') ?></label>
                                <input class="form-control votesComment" type="text" id="votesComment<?= $idadd ?>" value="">
                            </div>
                        </li>
                        <li>
                            <div class="votingData">
                                <div>
                                    <label for="votesYes<?= $idadd ?>"><?= Yii::t('amend', 'merge_amend_votes_yes') ?></label>
                                    <input class="form-control votesYes" type="number" id="votesYes<?= $idadd ?>" value="">
                                </div>
                                <div>
                                    <label for="votesNo<?= $idadd ?>"><?= Yii::t('amend', 'merge_amend_votes_no') ?></label>
                                    <input class="form-control votesNo" type="number" id="votesNo<?= $idadd ?>" value="">
                                </div>
                                <div>
                                    <label for="votesAbstention<?= $idadd ?>"><?= Yii::t('amend', 'merge_amend_votes_abstention') ?></label>
                                    <input class="form-control votesAbstention" type="number" id="votesAbstention<?= $idadd ?>" value="">
                                </div>
                                <div>
                                    <label for="votesInvalid<?= $idadd ?>"><?= Yii::t('amend', 'merge_amend_votes_invalid') ?></label>
                                    <input class="form-control votesInvalid" type="number" id="votesInvalid<?= $idadd ?>" value="">
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
                <?php
            }
            ?>
        </div>
        <div class="actions">
            <div class="mergeActionHolder hidden">
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-default dropdown-toggle dropdownAll" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                        <?= Yii::t('amend', 'merge_all') ?>
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
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
}
?>
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
            ?>" data-allow-diff-formattings="1" id="<?= $htmlId ?>_wysiwyg" title="">
                <div class="paragraphHolder" data-paragraph-no="<?= $paragraphNo ?>">
                    <?= $draftParagraph->text ?>
                </div>
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
