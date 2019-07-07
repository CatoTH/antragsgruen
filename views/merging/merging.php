<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\forms\MotionMergeAmendmentsForm;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var MotionMergeAmendmentsForm $form
 * @var array $amendmentStatuses
 * @var int[] $toMergeAmendmentIds
 * @var null|Motion $resumeDraft
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->robotsNoindex = true;
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(Yii::t('amend', 'merge_bread'));
$layout->loadFuelux();
$layout->loadCKEditor();

$title       = str_replace('%TITLE%', $motion->motionType->titleSingular, Yii::t('amend', 'merge_title'));
$this->title = $title . ': ' . $motion->getTitleWithPrefix();

$amendments = $motion->getVisibleAmendmentsSorted();

/** @var MotionSection[] $newSections */
$newSections = [];
foreach ($form->newMotion->getSortedSections(false) as $section) {
    $newSections[$section->sectionId] = $section;
}
if ($resumeDraft) {
    foreach ($resumeDraft->sections as $section) {
        if (!isset($newSections[$section->sectionId])) {
            $newSections[$section->sectionId] = $section;
        }
    }
}


echo '<h1 class="stickyHeader">' . $motion->getEncodedTitleWithPrefix() . '</h1>';

echo '<div class="motionData">';

if (!$motion->getMyConsultation()->getSettings()->minimalisticUI) {
    include(__DIR__ . '/../motion/_view_motiondata.php');
}

$hasCollidingParagraphs = false;
foreach ($motion->getSortedSections(false) as $section) {
    /** @var MotionSection $section */
    $type = $section->getSettings();
    if ($type->type === \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
        if (!isset($newSections[$section->sectionId])) {
            $diffMerger = $section->getAmendmentDiffMerger($toMergeAmendmentIds);
            if ($diffMerger->hasCollidingParagraphs()) {
                $hasCollidingParagraphs = true;
            }
        }
    }
}

if (count($amendments) > 0) {
    $explanation = Yii::t('amend', 'merge_explanation');
    if ($hasCollidingParagraphs) {
        $explanation = str_replace(
            '###COLLIDINGHINT###',
            Yii::t('amend', 'merge_explanation_colliding'),
            $explanation
        );
    } else {
        $explanation = str_replace('###COLLIDINGHINT###', '', $explanation);
    }
    $explanation = str_replace('###NEWPREFIX###', $motion->getNewTitlePrefix(), $explanation);
    echo '<div class="alert alert-info alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">' .
        '<span aria-hidden="true">&times;</span></button>' .
        $explanation . '</div>';
}

echo $controller->showErrors();

echo '</div>';

$amendmentStatuses = [];
foreach ($amendments as $amendment) {
    $amendmentStatuses[$amendment->id] = $amendment->status;
}

echo Html::beginForm(UrlHelper::createMotionUrl($motion, 'merge-amendments'), 'post', [
    'class'                    => 'motionMergeForm motionMergeStyles fuelux',
    'enctype'                  => 'multipart/form-data',
    'data-draft-saving'        => UrlHelper::createMotionUrl($motion, 'save-merging-draft'),
    'data-antragsgruen-widget' => 'frontend/MotionMergeAmendments',
    'data-amendment-statuses'  => $amendmentStatuses,
]);


$draftIsPublic   = ($resumeDraft && $resumeDraft->status === Motion::STATUS_MERGING_DRAFT_PUBLIC);
$publicDraftLink = UrlHelper::createMotionUrl($motion, 'merge-amendments-public');
$pdfLink         = UrlHelper::createMotionUrl($motion, 'merge-amendments-draft-pdf');
$resumedDate     = ($resumeDraft && $resumeDraft->getDateTime() ? $resumeDraft->getDateTime()->format('c') : '');
?>
    <section id="draftSavingPanel" data-resumed-date="<?= $resumedDate ?>">
        <h2>
            <?= Yii::t('amend', 'merge_draft_title') ?>
            <a href="<?= Html::encode($pdfLink) ?>" class="pdfLink" target="_blank">
                <span class="glyphicon glyphicon-download-alt"></span>
                PDF
            </a>
        </h2>
        <label class="public">
            <a href="<?= Html::encode($publicDraftLink) ?>" target="_blank"
               class="publicLink <?= ($draftIsPublic ? '' : 'hidden') ?>">
                <span class="glyphicon glyphicon-share"></span>
            </a>
            <input type="checkbox" name="public" <?= ($draftIsPublic ? 'checked' : '') ?>>
            <?= Yii::t('amend', 'merge_draft_public') ?>
        </label>
        <label class="autosave">
            <input type="checkbox" name="autosave" checked> <?= Yii::t('amend', 'merge_draft_auto_save') ?>
        </label>
        <div class="savingError hidden">
            <div class="errorNetwork"><?= Yii::t('amend', 'merge_draft_err_saving') ?></div>
            <div class="errorHolder"></div>
        </div>
        <div class="save">
            <div class="lastSaved">
                <?= Yii::t('amend', 'merge_draft_date') ?>:
                <span class="value"></span>
                <span class="none"><?= Yii::t('amend', 'merge_draft_not_saved') ?></span>
            </div>
            <button class="saveDraft btn btn-default btn-xs"
                    type="button"><?= Yii::t('amend', 'merge_draft_save') ?></button>
        </div>
    </section>

    <section class="newMotion">
        <h2 class="green"><?= Yii::t('amend', 'merge_new_text') ?></h2>
        <?php
        foreach ($motion->getSortedSections(false) as $section) {
            $type = $section->getSettings();
            if ($type->type === \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
                if (isset($newSections[$section->sectionId])) {
                    // @TODO
                    echo $newSections[$section->sectionId]->dataRaw;
                } else {
                    echo $this->render('_merging_section', [
                        'toMergeAmendmentIds' => $toMergeAmendmentIds,
                        'section'             => $section,
                    ]);
                }
            } else {
                echo '<div class="content">';
                if (isset($newSections[$section->sectionId])) {
                    echo $newSections[$section->sectionId]->getSectionType()->getMotionFormField();
                } else {
                    echo $section->getSectionType()->getMotionFormField();
                }

                if ($type->type === \app\models\sectionTypes\ISectionType::TYPE_TITLE) {
                    $changes = $section->getAmendingSections(false, true, true);
                    $changes = array_filter($changes, function ($section) use ($toMergeAmendmentIds) {
                        return in_array($section->amendmentId, $toMergeAmendmentIds);
                    });
                    /** @var \app\models\db\AmendmentSection[] $changes */
                    if (count($changes) > 0) {
                        echo '<div class="titleChanges">';
                        echo '<div class="title">' . Yii::t('amend', 'merge_title_changes') . '</div>';
                        foreach ($changes as $amendingSection) {
                            $titlePrefix = $amendingSection->getAmendment()->titlePrefix;
                            echo '<div class="change">';
                            echo '<div class="prefix">' . Html::encode($titlePrefix) . '</div>';
                            echo '<div class="text">' . Html::encode($amendingSection->data) . '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                }
                echo '</div>';
            }
        }
        ?>
    </section>
<?php


$editorials = [];
foreach ($motion->getVisibleAmendments(false) as $amendment) {
    if ($amendment->changeEditorial !== '') {
        $str          = '<div class="amendment content"><h3>';
        $str          .= str_replace(
            ['%TITLE%', '%INITIATOR%'],
            [Html::encode($amendment->titlePrefix), Html::encode($amendment->getInitiatorsStr())],
            Yii::t('amend', 'merge_amend_by')
        );
        $str          .= '</h3>';
        $str          .= '<div class="text">';
        $str          .= $amendment->changeEditorial;
        $str          .= '</div></div>';
        $editorials[] = $str;
    }
}
if (count($editorials) > 0) {
    ?>
    <section class="editorialAmendments">
        <h2 class="green"><?= Yii::t('amend', 'merge_amend_editorials') ?></h2>
        <div><?= implode('', $editorials) ?></div>
    </section>
    <?php
}

?>

    <div class="submitHolder content">
        <button type="submit" name="save" class="btn btn-primary">
            <span class="glyphicon glyphicon-chevron-right"></span> <?= Yii::t('amend', 'go_on') ?>
        </button>
    </div>

<?php
echo Html::endForm();
