<?php
/**
 * @var MotionSection $section
 * @var int[] $toMergeMainIds
 * @var Amendment[] $amendmentsById
 * @var \app\components\diff\amendmentMerger\SectionMerger $merger
 * @var \app\components\diff\amendmentMerger\SectionMerger $mergerAll
 * @var int $paragraphNo
 */

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\MotionSection;
use yii\helpers\Html;


$CHANGESET_COUNTER = 0;
$changeset         = [];

$paragraphMerger     = $merger->getParagraphMerger($paragraphNo);
$paragraphCollisions = $paragraphMerger->getCollidingParagraphGroups();
$paragraphText       = $paragraphMerger->getFormattedDiffText($amendmentsById);

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
     'data-reload-url="' . Html::encode($reloadUrl) . '">';

$allAmendingIds  = $mergerAll->getAffectingAmendmentIds($paragraphNo);
$currAmendingIds = $merger->getAffectingAmendmentIds($paragraphNo);
if (count($allAmendingIds) > 0) {
    ?>
    <div class="changeToolbar">
        <div class="statuses">
            <?php
            $modUs = [];
            /** @var Amendment[] $normalAmendments */
            $normalAmendments = [];
            foreach ($allAmendingIds as $amendingId) {
                $amendment = $amendmentsById[$amendingId];
                if ($amendment->status === Amendment::STATUS_PROPOSED_MODIFIED_AMENDMENT) {
                    $modUs[$amendment->id] = $amendment;
                } else {
                    $normalAmendments[$amendment->id] = $amendment;
                }
            }
            foreach ($modUs as $amendment) {
                // ModUs that modify a paragraph unaffected by the original amendment
                $normalAmendments[$amendment->proposalReferencedBy->id] = $amendment->proposalReferencedBy;
            }
            if (count($normalAmendments) > 0) {
                $normalAmendments = array_values($normalAmendments);
                $normalAmendments = \app\components\MotionSorter::getSortedAmendments($normalAmendments[0]->getMyConsultation(), $normalAmendments);
            }
            foreach ($normalAmendments as $amendment) {
                $active       = in_array($amendment->id, $toMergeMainIds);
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

                ?>
                <div class="btn-group amendmentStatus" data-amendment-id="<?= $amendment->id ?>">
                    <button type="button" class="btn btn-<?= ($active ? 'success' : 'default') ?> btn-xs toggleAmendment">
                        <input name="<?= $nameBase ?>[<?= $amendment->id ?>]" value="<?= ($active ? '1' : '0') ?>"
                               type="hidden" class="amendmentActive" data-amendment-id="<?= $amendment->id ?>">
                        <?= ($amendment->titlePrefix ? Html::encode($amendment->titlePrefix) : '-') ?>
                    </button>
                    <button class="btn btn-<?= ($active ? 'success' : 'default') ?> btn-xs dropdown-toggle"
                            type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="<?= Html::encode($amendmentUrl) ?>" class="amendmentLink" target="_blank">
                                <span class="glyphicon glyphicon-new-window"></span>
                                <?= Yii::t('amend', 'merge_amend_show') ?>
                            </a>
                        </li>
                        <?php
                        if ($amendment->proposalReference) {
                            ?>
                            <li role="separator" class="divider"></li>
                            <li class="versionorig">
                                <a href="#" class="setVersion" data-version="orig">
                                    <?= Yii::t('amend', 'merge_amtable_text_orig') ?>
                                </a>
                            </li>
                            <li class="versionprop">
                                <a href="#" class="setVersion" data-version="prop">
                                    <?= Yii::t('amend', 'merge_amtable_text_prop') ?>
                                </a>
                            </li>
                            <?php
                        }
                        ?>
                        <li role="separator" class="divider dividerLabeled" data-label="Set status:"></li>
                        <?php
                        foreach ($statuses as $statusId => $statusName) {
                            echo '<li class="status' . $statusId . '">' .
                                 '<a href="" class="setStatus" data-status="' . $statusId . '">' .
                                 Html::encode($statusName) . '</a></li>';
                        }
                        ?>
                    </ul>
                </div>
                <?php
            }
            ?>
        </div>
        <div class="actions">
            <div class="changedIndicator unchanged"><?= Yii::t('amend', 'merge_changed') ?></div>
            <div class="mergeActionHolder hidden">
                <div class="btn-group">
                    <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown"
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
        <div class="wysiwyg-textarea" id="<?= $holderId ?>" data-fullHtml="0">
            <!--suppress HtmlFormInputWithoutLabel -->
            <textarea name="<?= $nameBase ?>[raw]" class="raw" id="<?= $htmlId ?>"
                      title="<?= Html::encode($type->title) ?>"></textarea>
            <!--suppress HtmlFormInputWithoutLabel -->
            <textarea name="<?= $nameBase ?>[consolidated]" class="consolidated"
                      title="<?= Html::encode($type->title) ?>"></textarea>
            <div class="texteditor motionTextFormattings boxed ICE-Tracking<?php
            if ($section->getSettings()->fixedWidth) {
                echo ' fixedWidthFont';
            }
            ?>'" data-allow-diff-formattings="1" id="<?= $htmlId ?>_wysiwyg" title="">
                <div class="paragraphHolder" data-paragraph-no="<?= $paragraphNo ?>">
                    <?= $paragraphText ?>
                </div>
            </div>
        </div>
        <div class="collisionsHolder">
            <?php
            foreach ($paragraphCollisions as $amendmentId => $paraData) {
                $amendment = $amendmentsById[$amendmentId];
                echo $paragraphMerger->getFormattedCollision($paraData, $amendment, $amendmentsById);
            }
            ?>
        </div>
    </div>

<?php
echo '</section>';
