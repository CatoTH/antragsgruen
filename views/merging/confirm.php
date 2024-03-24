<?php

use app\components\{HTMLTools, UrlHelper};
use app\models\db\{Amendment, Motion};
use app\models\mergeAmendments\Draft;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Motion $oldMotion
 * @var Motion $newMotion
 * @var Draft $mergingDraft
 * @var \app\models\MotionSectionChanges[] $changes
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->robotsNoindex = true;
$layout->addBreadcrumb($newMotion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($newMotion));
$layout->addBreadcrumb(Yii::t('amend', 'merge_confirm_title'));
$layout->loadDatepicker();

$title       = str_replace('%TITLE%', $newMotion->getMyMotionType()->titleSingular, Yii::t('amend', 'merge_title'));
$this->title = $title . ': ' . $newMotion->getTitleWithPrefix();

?>
    <h1><?= Yii::t('amend', 'merge_confirm_title') ?></h1>
<?php

echo Html::beginForm('', 'post', [
    'id'                       => 'motionConfirmForm',
    'class'                    => 'motionMergeConfirmForm',
    'data-antragsgruen-widget' => 'frontend/MotionMergeAmendmentsConfirm'
]);

$odtText = '<span class="glyphicon glyphicon-download" aria-hidden="true"></span> ' . Yii::t('amend', 'merge_confirm_odt');
$odtLink = UrlHelper::createMotionUrl($newMotion, 'view-changes-odt');
?>
    <section class="toolbarBelowTitle mergeConfirmToolbar">
        <div class="styleSwitcher">
            <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-default active">
                    <input type="radio" name="diffStyle" value="full" autocomplete="off" checked>
                    <?= Yii::t('amend', 'merge_confirm_full') ?>
                </label>
                <label class="btn btn-default">
                    <input type="radio" name="diffStyle" value="diff" autocomplete="off">
                    <?= Yii::t('amend', 'merge_confirm_diff') ?>
                </label>
            </div>
        </div>
        <div class="export">
            <?= Html::a($odtText, $odtLink, ['class' => 'btn btn-default']) ?>
        </div>
    </section>
<?php

if ($newMotion->canCreateResolution()) {
    echo $this->render('_confirm_resolution_voting', ['motion' => $newMotion, 'oldMotion' => $oldMotion]);
}

foreach ($newMotion->getSortedSections(true) as $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }
    echo '<section class="motionTextHolder">';

    echo '<div class="fullText">';
    echo '<h2 class="green">' . Html::encode($section->getSettings()->title) . '</h2>';
    echo $section->getSectionType()->getSimple(false);
    echo '</div>';

    foreach ($changes as $change) {
        echo '<div class="diffText">';
        if ($change->getSectionId() === $section->sectionId) {
            echo $this->render('@app/views/motion/_view_change_section', ['change' => $change]);
        }
        echo '</div>';
    }

    echo '</section>';
}
if (count($newMotion->replacedMotion->getVisibleAmendments()) > 0) {
    ?>
    <section class="newAmendments">
        <h2 class="green"><?= Yii::t('amend', 'merge_amend_statuses') ?></h2>
        <div class="content">
            <?php
            foreach ($newMotion->replacedMotion->getVisibleAmendments() as $amendment) {
                //$changeset = (isset($changesets[$amendment->id]) ? $changesets[$amendment->id] : []);
                $changeset = [];
                $data      = 'data-old-status="' . $amendment->status . '"';
                $data      .= ' data-amendment-id="' . $amendment->id . '"';
                $data      .= ' data-changesets="' . Html::encode(json_encode($changeset)) . '"';
                $voting    = $mergingDraft->amendmentVotingData[$amendment->id];
                ?>
                <div class="form-group amendmentStatus amendmentStatus<?= $amendment->id ?>" <?= $data ?>>
                    <div class="titleHolder">
                        <div class="amendmentName">
                            <?= Html::encode($amendment->getShortTitle()) ?>
                        </div>
                        <div class="amendSubtitle"><?= Html::encode($amendment->getInitiatorsStr()) ?></div>
                    </div>
                    <div class="statusHolder">
                        <?= HTMLTools::amendmentDiffTooltip($amendment) ?>
                        <label for="amendmentStatus<?= $amendment->id ?>">Status:</label><br>
                        <?php
                        $statusesAll                  = $amendment->getMyConsultation()->getStatuses()->getStatusNames();
                        $statuses                     = [
                            Amendment::STATUS_PROCESSED         => $statusesAll[Amendment::STATUS_PROCESSED],
                            Amendment::STATUS_ACCEPTED          => $statusesAll[Amendment::STATUS_ACCEPTED],
                            Amendment::STATUS_REJECTED          => $statusesAll[Amendment::STATUS_REJECTED],
                            Amendment::STATUS_MODIFIED_ACCEPTED => $statusesAll[Amendment::STATUS_MODIFIED_ACCEPTED],
                        ];
                        $statuses[$amendment->status] = $statusesAll[$amendment->status];
                        $statusPre = $mergingDraft->amendmentStatuses[$amendment->id] ?? Amendment::STATUS_PROCESSED;
                        $opts = ['id' => 'amendmentStatus' . $amendment->id, 'class' => 'stdDropdown'];
                        echo Html::dropDownList('amendStatus[' . $amendment->id . ']', $statusPre, $statuses, $opts, true);
                        ?>
                    </div>
                    <div class="commentHolder">
                        <label for="votesComment<?= $amendment->id ?>"><?= Yii::t('amend', 'merge_new_votes_comment') ?></label>
                        <input class="form-control" name="amendVotes[<?= $amendment->id ?>][comment]" type="text"
                               id="votesComment<?= $amendment->id ?>"
                               value="<?= Html::encode($voting->comment ?: '') ?>">
                    </div>
                    <div class="votesHolder">
                        <label for="votesYes<?= $amendment->id ?>"><?= Yii::t('amend', 'merge_amend_votes_yes') ?></label>
                        <input class="form-control" name="amendVotes[<?= $amendment->id ?>][yes]" type="number"
                               id="votesYes<?= $amendment->id ?>"
                               value="<?= Html::encode($voting->votesYes ?: '') ?>">
                    </div>
                    <div class="votesHolder">
                        <label for="votesNo<?= $amendment->id ?>"><?= Yii::t('amend', 'merge_amend_votes_no') ?></label>
                        <input class="form-control" name="amendVotes[<?= $amendment->id ?>][no]" type="number"
                               id="votesNo<?= $amendment->id ?>"
                               value="<?= Html::encode($voting->votesNo ?: '') ?>">
                    </div>
                    <div class="votesHolder">
                        <label for="votesAbstention<?= $amendment->id ?>"><?= Yii::t('amend', 'merge_amend_votes_abstention') ?></label>
                        <input class="form-control" name="amendVotes[<?= $amendment->id ?>][abstention]" type="number"
                               id="votesAbstention<?= $amendment->id ?>"
                               value="<?= Html::encode($voting->votesAbstention ?: '') ?>">
                    </div>
                    <div class="votesHolder">
                        <label for="votesInvalid<?= $amendment->id ?>"><?= Yii::t('amend', 'merge_amend_votes_invalid') ?></label>
                        <input class="form-control" name="amendVotes[<?= $amendment->id ?>][invalid]" type="number"
                               id="votesInvalid<?= $amendment->id ?>"
                               value="<?= Html::encode($voting->votesInvalid ?: '') ?>">
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </section>
    <?php
}

?>
    <div class="content saveCancelRow">
        <div class="saveCol">
            <button type="submit" name="confirm" class="btn btn-success">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <?= Yii::t('base', 'save') ?>
            </button>
        </div>
        <div class="cancelCol">
            <button type="submit" name="modify" class="btn">
                <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
                <?= Yii::t('amend', 'button_correct') ?>
            </button>
        </div>
    </div>
<?php
echo Html::endForm();
