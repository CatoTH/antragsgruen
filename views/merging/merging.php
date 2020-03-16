<?php

use app\components\UrlHelper;
use app\models\db\MotionSection;
use app\models\mergeAmendments\Init;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Init $form
 * @var bool $twoCols
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
$motion     = $form->motion;

$layout->robotsNoindex = true;
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(Yii::t('amend', 'merge_bread'));
$layout->loadFuelux();
$layout->loadCKEditor();
if ($twoCols) {
    $layout->fullWidth = true;
    //$layout->fullScreen = true;
}

$title       = str_replace('%TITLE%', $motion->motionType->titleSingular, Yii::t('amend', 'merge_title'));
$this->title = $title . ': ' . $motion->getTitleWithPrefix();

$amendments = $motion->getVisibleAmendmentsSorted();

/** @var MotionSection[] $newSections */
$newSections = [];
foreach ($motion->getSortedSections(false) as $section) {
    $newSections[$section->sectionId] = $section;
}


echo '<h1 class="stickyHeader">' . $motion->getEncodedTitleWithPrefix() . '</h1>';

echo '<div class="motionData">';

include(__DIR__ . '/../motion/_view_motiondata.php');

if (count($amendments) > 0) {
    $explanation = Yii::t('amend', 'merge_explanation');
    $explanation = str_replace('###COLLIDINGHINT###', '', $explanation);
    $explanation = str_replace('###NEWPREFIX###', $motion->getNewTitlePrefix(), $explanation);
    echo '<div class="alert alert-info alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close">' .
         '<span aria-hidden="true">&times;</span></button>' .
         $explanation . '</div>';
}

echo $controller->showErrors();

echo '</div>';

echo Html::beginForm(UrlHelper::createMotionUrl($motion, 'merge-amendments'), 'post', [
    'class'                    => 'motionMergeForm motionMergeStyles fuelux',
    'enctype'                  => 'multipart/form-data',
    'data-draft-saving'        => UrlHelper::createMotionUrl($motion, 'save-merging-draft'),
    'data-antragsgruen-widget' => 'frontend/MotionMergeAmendments',
]);

$publicDraftLink = UrlHelper::createMotionUrl($motion, 'merge-amendments-public');
$pdfLink         = UrlHelper::createMotionUrl($motion, 'merge-amendments-draft-pdf');
$resumedDate     = ($form->draftData->time ? $form->draftData->time->format('c') : '');
?>
    <section id="draftSavingPanel" data-resumed-date="<?= ($resumedDate) ?>">
        <h2>
            <?= Yii::t('amend', 'merge_draft_title') ?>
            <a href="<?= Html::encode($pdfLink) ?>" class="pdfLink" target="_blank">
                <span class="glyphicon glyphicon-download-alt"></span>
                PDF
            </a>
        </h2>
        <label class="public">
            <a href="<?= Html::encode($publicDraftLink) ?>" target="_blank"
               class="publicLink <?= ($form->draftData->public ? '' : 'hidden') ?>">
                <span class="glyphicon glyphicon-share"></span>
            </a>
            <input type="checkbox" name="public" <?= ($form->draftData->public ? 'checked' : '') ?>>
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
            if ($type->type === \app\models\sectionTypes\ISectionType::TYPE_TITLE) {
                echo $this->render('_merging_section_title', ['form' => $form, 'section' => $section, 'twoCols' => $twoCols]);
            } elseif ($type->type === \app\models\sectionTypes\ISectionType::TYPE_TEXT_SIMPLE) {
                echo $this->render('_merging_section', ['form' => $form, 'section' => $section, 'twoCols' => $twoCols]);
            } else {
                echo $this->render('_merging_section_other', ['form' => $form, 'section' => $section, 'twoCols' => $twoCols]);
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

echo Html::input('hidden', 'mergeDraft', json_encode($form->draftData), ['id' => 'mergeDraft']);
?>
    <div class="submitHolder content">
        <button type="submit" name="save" class="btn btn-primary">
            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
            <?= Yii::t('amend', 'go_on') ?>
        </button>
    </div>

<?php
echo Html::endForm();
