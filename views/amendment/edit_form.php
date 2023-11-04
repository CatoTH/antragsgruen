<?php

use app\components\UrlHelper;
use app\models\policies\IPolicy;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\AmendmentEditForm $form
 * @var \app\models\db\Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$motionType = $form->motion->getMyMotionType();
$multipleParagraphs = ($motionType->amendmentMultipleParagraphs === \app\models\db\ConsultationMotionType::AMEND_PARAGRAPHS_MULTIPLE);

if ($form->motion->titlePrefix !== '') {
    if ($consultation->getSettings()->hideTitlePrefix) {
        $title = Yii::t('amend', $mode === 'create' ? 'amendment_create' : 'amendment_edit');
    } else {
        $title = Yii::t('amend', $mode === 'create' ? 'amendment_create_x' : 'amendment_edit_x');
    }
    $this->title = str_replace('%prefix%', $form->motion->getTitleWithPrefix(), $title);
} else {
    $this->title = Yii::t('amend', $mode === 'create' ? 'amendment_create' : 'amendment_edit');
}

$layout->robotsNoindex = true;
$layout->loadCKEditor();
$layout->addBreadcrumb($motionType->titleSingular, UrlHelper::createMotionUrl($form->motion));
if ($form->toAnotherAmendment) {
    $amendingAmendment = $consultation->getAmendment($form->toAnotherAmendment);
    $layout->addBreadcrumb($amendingAmendment->getFormattedTitlePrefix(), UrlHelper::createAmendmentUrl($amendingAmendment));
}
$layout->addBreadcrumb(Yii::t('amend', $mode === 'create' ? 'amendment_create' : 'amendment_edit'));

echo '<h1>' . Html::encode($this->title) . '</h1>';

echo '<div class="form content">';

echo '<br><div class="alert alert-info">';

if ($form->toAnotherAmendment) {
    echo $motionType->getConsultationTextWithFallback('amend', 'create_explanation_amendtoamend');
} elseif ($motionType->amendmentsOnly) {
    echo $motionType->getConsultationTextWithFallback('amend', 'create_explanation_statutes');
} else {
    echo $motionType->getConsultationTextWithFallback('amend', 'create_explanation');
}
echo '</div><br style="clear: both;">';


echo $controller->showErrors();

if ($motionType->getAmendmentSupportTypeClass()->collectSupportersBeforePublication()) {
    /** @var \app\models\supportTypes\CollectBeforePublish $supp */
    $supp = $motionType->getAmendmentSupportTypeClass();
    $str  = $motionType->getConsultationTextWithFallback('amend', 'support_collect_explanation');
    $str  = str_replace('%MIN%', $supp->getSettingsObj()->minSupporters, $str);
    $str  = str_replace('%MIN+1%', ($supp->getSettingsObj()->minSupporters + 1), $str);

    echo '<div style="font-weight: bold; text-decoration: underline;">' .
        $motionType->getConsultationTextWithFallback('amend', 'support_collect_explanation_title') . '</div>' .
        $str . '<br><br>';
}

$amendmentPolicy = $motionType->getAmendmentPolicy();
if (!in_array($amendmentPolicy::getPolicyID(), [IPolicy::POLICY_ALL, IPolicy::POLICY_LOGGED_IN])) {
    echo '<div><legend>' . Yii::t('amend', 'amendment_requirement'), '</legend></div>';
    echo $amendmentPolicy->getOnCreateDescription();
}

if (Yii::$app->user->isGuest) {
    echo \app\components\AntiSpam::getJsProtectionHint((string)$form->motion->id);
}

echo '<div id="draftHint" class="hidden alert alert-info"
    data-motion-id="' . $form->motion->id . '" data-amendment-id="' . $form->amendmentId . '">' .
    Yii::t('amend', 'unsaved_drafts') . '<ul></ul>
</div>

</div>';


echo Html::beginForm('', 'post', [
    'id'                        => 'amendmentEditForm',
    'class'                     => 'motionEditForm draftForm',
    'enctype'                   => 'multipart/form-data',
    'data-antragsgruen-widget'  => 'frontend/AmendmentEdit',
    'data-multi-paragraph-mode' => $motionType->amendmentMultipleParagraphs,
    'data-init-section-id'      => $form->initSectionId,
    'data-init-paragraph-no'    => $form->initParagraphNo,
]);

echo '<h2 class="green">' . Yii::t('amend', 'merge_new_text') . '</h2>';

$globalAlternatives = ($consultation->getSettings()->globalAlternatives && $multipleParagraphs);
if ($consultation->getSettings()->editorialAmendments || $globalAlternatives) {
    echo '<section class="editorialGlobalBar">';
    if ($globalAlternatives) {
        echo '<label>' . Html::checkbox('globalAlternative', $form->globalAlternative) .
            Yii::t('amend', 'global_alternative') . '</label>';
    }
    if ($consultation->getSettings()->editorialAmendments) {
        echo '<label class="editorialChange">' . Html::checkbox('editorialChange', $form->editorial != '') .
            Yii::t('amend', 'editorial_hint') . '</label>';
    }
    echo '</section>';
}

echo '<div class="content">';


if ($consultation->getSettings()->amendmentsHaveTags) {
    echo $this->render('@app/views/shared/edit_tags', ['consultation' => $consultation, 'tagIds' => $form->tags]);
}

if ($consultation->getSettings()->editorialAmendments) { ?>
    <div class="form-group wysiwyg-textarea hidden" id="sectionHolderEditorial"
         data-full-html="0" data-max-len="0">
        <label for="amendmentEditorial"><?= Yii::t('amend', 'editorial_hint') ?></label>
        <textarea name="amendmentEditorial" id="amendmentEditorial"
                  class="raw"><?= Html::encode($form->editorial) ?></textarea>
        <div class="texteditor motionTextFormattings boxed"
             id="amendmentEditorial_wysiwyg"><?= $form->editorial ?></div>
    </div>
    <?php
}

foreach ($form->sections as $section) {
    echo $section->getSectionType()->getAmendmentFormField();
}

echo '</div>';

?>
<h2 class="green"><?= Yii::t('amend', 'reason') ?></h2>
<div class="content">
    <div class="form-group wysiwyg-textarea" data-maxLen="0" data-fullHtml="0" id="amendmentReasonHolder">
        <label for="amendmentReason"><?= Yii::t('amend', 'reason') ?></label>
        <textarea name="amendmentReason" id="amendmentReason" class="raw"><?= Html::encode($form->reason) ?></textarea>
        <div class="texteditor motionTextFormattings boxed" id="amendmentReason_wysiwyg"><?= $form->reason ?></div>
    </div>
</div>

<?php

if ($form->getAllowEditinginitiators()) {
    $initiatorClass = $form->motion->motionType->getAmendmentSupportTypeClass();
    echo $initiatorClass->getAmendmentForm($form->motion->motionType, $form, $controller);
}

if (!$multipleParagraphs) {
    echo '<input type="hidden" name="modifiedSectionId" value="">';
    echo '<input type="hidden" name="modifiedParagraphNo" value="">';
}
if ($form->toAnotherAmendment) {
    echo '<input type="hidden" name="createFromAmendment" value="' . Html::encode($form->toAnotherAmendment) . '">';
}

?>
<section class="content saveCancelRow">
    <div class="saveCol">
        <button type="submit" name="save" class="btn btn-primary">
            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
            <?= Yii::t('amend', 'go_on') ?>
        </button>
    </div>
    <div class="cancelCol">
        <a href="<?= Html::encode(UrlHelper::createMotionUrl($form->motion)) ?>" id="cancel" class="btn">
            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
            <?= Yii::t('amend', 'sidebar_back') ?>
        </a>
    </div>
</section>

<?php
echo Html::endForm();
