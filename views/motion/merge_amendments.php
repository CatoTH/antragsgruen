<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\forms\MotionMergeAmendmentsForm;
use app\models\sectionTypes\TextSimple;
use \app\views\motion\LayoutHelper as MotionLayoutHelper;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var MotionMergeAmendmentsForm $form
 * @var array $amendmentStati
 * @var int[] $toMergeAmendmentIds
 * @var null|Motion $resumeDraft
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->robotsNoindex = true;
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(\Yii::t('amend', 'merge_bread'));
$layout->loadFuelux();
$layout->loadCKEditor();

$title       = str_replace('%TITLE%', $motion->motionType->titleSingular, \Yii::t('amend', 'merge_title'));
$this->title = $title . ': ' . $motion->getTitleWithPrefix();

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


echo '<h1>' . $motion->getEncodedTitleWithPrefix() . '</h1>';

echo '<div class="motionData">';

if (!$motion->getMyConsultation()->getSettings()->minimalisticUI) {
    include(__DIR__ . DIRECTORY_SEPARATOR . '_view_motiondata.php');
}

$hasCollidingParagraphs = false;
foreach ($motion->getSortedSections(false) as $section) {
    /** @var MotionSection $section */
    $type = $section->getSettings();
    if ($type->type == \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
        if (!isset($newSections[$section->sectionId])) {
            $diffMerger = $section->getAmendmentDiffMerger($toMergeAmendmentIds);
            if ($diffMerger->hasCollodingParagraphs()) {
                $hasCollidingParagraphs = true;
            }
        }
    }
}

$explanation = \Yii::t('amend', 'merge_explanation');
if ($hasCollidingParagraphs) {
    $explanation = str_replace('###COLLIDINGHINT###', \Yii::t('amend', 'merge_explanation_colliding'), $explanation);
} else {
    $explanation = str_replace('###COLLIDINGHINT###', '', $explanation);
}
$explanation = str_replace('###NEWPREFIX###', $motion->getNewTitlePrefix(), $explanation);
echo '<div class="alert alert-info alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">' .
    '<span aria-hidden="true">&times;</span></button>' .
    $explanation . '</div>';


echo $controller->showErrors();

echo '</div>';


echo Html::beginForm(UrlHelper::createMotionUrl($motion, 'merge-amendments'), 'post', [
    'class'                    => 'motionMergeForm motionMergeStyles fuelux',
    'enctype'                  => 'multipart/form-data',
    'data-draft-saving'        => UrlHelper::createMotionUrl($motion, 'save-merging-draft'),
    'data-antragsgruen-widget' => 'frontend/MotionMergeAmendments',
]);


$draftIsPublic   = ($resumeDraft && $resumeDraft->status == Motion::STATUS_MERGING_DRAFT_PUBLIC);
$publicDraftLink = UrlHelper::createMotionUrl($motion, 'merge-amendments-public');
?>
    <section id="draftSavingPanel" data-resumed-date="<?= ($resumeDraft ? $resumeDraft->dateCreation : '') ?>">
        <h2><?= \Yii::t('amend', 'merge_draft_title') ?></h2>
        <label class="public">
            <a href="<?= Html::encode($publicDraftLink) ?>" target="_blank"
               class="publicLink <?= ($draftIsPublic ? '' : 'hidden') ?>">
                <span class="glyphicon glyphicon-share"></span>
            </a>
            <input type="checkbox" name="public" <?= ($draftIsPublic ? 'checked' : '') ?>>
            <?= \Yii::t('amend', 'merge_draft_public') ?>
        </label>
        <label class="autosave">
            <input type="checkbox" name="autosave" checked> <?= \Yii::t('amend', 'merge_draft_auto_save') ?>
        </label>
        <div class="savingError hidden">
            <div class="errorNetwork"><?= \Yii::t('amend', 'merge_draft_err_saving') ?></div>
            <div class="errorHolder"></div>
        </div>
        <div class="save">
            <div class="lastSaved">
                <?= \Yii::t('amend', 'merge_draft_date') ?>:
                <span class="value"></span>
                <span class="none"><?= \Yii::t('amend', 'merge_draft_not_saved') ?></span>
            </div>
            <button class="saveDraft btn btn-default btn-xs"
                    type="button"><?= \Yii::t('amend', 'merge_draft_save') ?></button>
        </div>
    </section>

    <section class="newMotion">
        <h2 class="green"><?= \Yii::t('amend', 'merge_new_text') ?></h2>
        <div class="content">

            <?php
            $changesets = [];

            foreach ($motion->getSortedSections(false) as $section) {
                $type = $section->getSettings();
                if ($type->type == \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
                    /** @var TextSimple $simpleSection */
                    $simpleSection = $section->getSectionType();

                    $nameBase = 'sections[' . $type->id . ']';
                    $htmlId   = 'sections_' . $type->id;
                    $holderId = 'section_holder_' . $type->id;

                    echo '<div class="form-group wysiwyg-textarea" id="' . $holderId . '" data-fullHtml="0">';
                    echo '<label for="' . $htmlId . '">' . Html::encode($type->title) . '</label>';

                    echo '<textarea name="' . $nameBase . '[raw]" class="raw" id="' . $htmlId . '" ' .
                        'title="' . Html::encode($type->title) . '"></textarea>';
                    echo '<textarea name="' . $nameBase . '[consolidated]" class="consolidated" ' .
                        'title="' . Html::encode($type->title) . '"></textarea>';
                    echo '<div class="texteditor boxed ICE-Tracking';
                    if ($section->getSettings()->fixedWidth) {
                        echo ' fixedWidthFont';
                    }
                    echo '" data-allow-diff-formattings="1" ' .
                        'id="' . $htmlId . '_wysiwyg" title="">';

                    if (isset($newSections[$section->sectionId])) {
                        echo $newSections[$section->sectionId]->dataRaw;
                    } else {
                        echo $simpleSection->getMotionTextWithInlineAmendments($toMergeAmendmentIds, $changesets);
                    }

                    echo '</div>';

                    echo '<div class="mergeActionHolder" style="margin-top: 5px; margin-bottom: 5px;">';
                    echo '<button type="button" class="acceptAllChanges btn btn-small btn-default">' .
                        \Yii::t('amend', 'merge_accept_all') . '</button> ';
                    echo '<button type="button" class="rejectAllChanges btn btn-small btn-default">' .
                        \Yii::t('amend', 'merge_reject_all') . '</button>';
                    echo '</div>';

                    echo '</div>';
                } else {
                    if (isset($newSections[$section->sectionId])) {
                        echo $newSections[$section->sectionId]->getSectionType()->getMotionFormField();
                    } else {
                        echo $section->getSectionType()->getMotionFormField();
                    }
                }
            }

            ?>
        </div>
    </section>
<?php


$editorials = [];
foreach ($motion->getVisibleAmendments(false) as $amendment) {
    if ($amendment->changeEditorial != '') {
        $str          = '<div class="amendment content"><h3>';
        $str          .= str_replace(
            ['%TITLE%', '%INITIATOR%'],
            [$amendment->titlePrefix, $amendment->getInitiatorsStr()],
            \Yii::t('amend', 'merge_amend_by')
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
        <h2 class="green"><?= \Yii::t('amend', 'merge_amend_editorials') ?></h2>
        <div><?= implode('', $editorials) ?></div>
    </section>
    <?php
}


$jsStati = [
    'processed'         => Amendment::STATUS_PROCESSED,
    'accepted'          => Amendment::STATUS_ACCEPTED,
    'rejected'          => Amendment::STATUS_REJECTED,
    'modified_accepted' => Amendment::STATUS_MODIFIED_ACCEPTED,
];

?>

    <section class="newAmendments" data-stati="<?= Html::encode(json_encode($jsStati)) ?>">
        <?php MotionLayoutHelper::printAmendmentStatusSetter($motion->getVisibleAmendmentsSorted(), $amendmentStati); ?>
    </section>


    <div class="submitHolder content">
        <button type="submit" name="save" class="btn btn-primary">
            <span class="glyphicon glyphicon-chevron-right"></span> <?= \Yii::t('amend', 'go_on') ?>
        </button>
    </div>

<?php
echo Html::endForm();
