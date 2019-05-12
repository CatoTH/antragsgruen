<?php
/**
 * @var MotionSection $section
 * @var int[] $toMergeAmendmentIds
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
    'sectionId'    => $type->id,
    'paragraphNo'  => $paragraphNo,
    'amendmentIds' => 'DUMMY',
]);

echo '<section class="paragraphWrapper" data-section-id="' . $type->id . '" data-paragraph-id="' . $paragraphNo . '" ' .
    'data-reload-url="' . Html::encode($reloadUrl) . '">';

$allAmendingIds  = $mergerAll->getAffectingAmendmentIds($paragraphNo);
$currAmendingIds = $merger->getAffectingAmendmentIds($paragraphNo);
if (count($allAmendingIds) > 0) {
    ?>
    <div>
        <?php
        foreach ($allAmendingIds as $amendingId) {
            $amendment = $amendmentsById[$amendingId];
            $active    = in_array($amendingId, $currAmendingIds);
            ?>
            <div class="btn-group">
                <button type="button" class="btn btn-<?= ($active ? 'success' : 'default') ?> btn-xs toggleAmendment">
                    <input name="<?= $nameBase ?>[<?= $amendingId ?>]" value="<?= ($active ? '1' : '0') ?>"
                           type="hidden" class="amendmentActive" data-amendment-id="<?= $amendingId ?>">
                    <?= Html::encode($amendment->titlePrefix) ?>
                </button>
                <button class="btn btn-<?= ($active ? 'success' : 'default') ?> btn-xs dropdown-toggle"
                        type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="caret"></span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="#">Action</a></li>
                    <li><a href="#">Another action</a></li>
                    <li><a href="#">Something else here</a></li>
                    <li role="separator" class="divider"></li>
                    <li><a href="#">Separated link</a></li>
                </ul>
            </div>
            <?php
        }
        ?>
        <div class="mergeActionHolder pull-right">
            <div class="btn-group">
                <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                    <?= Yii::t('amend', 'merge_all') ?>
                    <span class="caret"></span>
                    <span class="sr-only">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="#"><?= Yii::t('amend', 'merge_accept_all') ?></a></li>
                    <li><a href="#"><?= Yii::t('amend', 'merge_reject_all') ?></a></li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}
?>
    <div class="form-group wysiwyg-textarea" id="<?= $holderId ?>" data-fullHtml="0">
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
            <div class="paragraphHolder<?= (count($paragraphCollisions) > 0 ? ' hasCollisions' : '') ?>"
                 data-paragraph-no="<?= $paragraphNo ?>">
                <?= $paragraphText ?>
            </div>
        </div>
        <div class="collissionsHolder">
            <?php
            foreach ($paragraphCollisions as $amendmentId => $paraData) {
                $amendment    = $amendmentsById[$amendmentId];
                echo $paragraphMerger->getFormattedCollission($paraData, $amendment, $amendmentsById);
            }
            ?>
        </div>
    </div>

<?php
echo '</section>';
