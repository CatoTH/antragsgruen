<?php

use app\components\UrlHelper;
use app\models\policies\IPolicy;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\AmendmentEditForm $form
 * @var \app\models\db\Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$multipleParagraphs = $form->motion->motionType->amendmentMultipleParagraphs;

if ($form->motion->titlePrefix != '') {
    if ($consultation->getSettings()->hideTitlePrefix) {
        $title = Yii::t(
            'amend',
            $mode == 'create' ? 'amendment_create' : 'amendment_edit'
        );
    } else {
        $title = Yii::t(
            'amend',
            $mode == 'create' ? 'amendment_create_x' : 'amendment_edit_x'
        );
    }
    $this->title = str_replace('%prefix%', $form->motion->titlePrefix, $title);
} else {
    $this->title = Yii::t('amend', $mode == 'create' ? 'amendment_create' : 'amendment_edit');
}

$layout->robotsNoindex = true;
$layout->loadCKEditor();
$layout->addBreadcrumb($form->motion->motionType->titleSingular, UrlHelper::createMotionUrl($form->motion));
$layout->addBreadcrumb(Yii::t('amend', $mode == 'create' ? 'amendment_create' : 'amendment_edit'));

echo '<h1>' . Html::encode($this->title) . '</h1>';

echo '<div class="form content">';

echo '<br><div class="alert alert-info" role="alert">';
echo \Yii::t('amend', 'create_explanation');
echo '</div><br style="clear: both;">';


echo $controller->showErrors();

if ($form->motion->motionType->getAmendmentSupportTypeClass()->collectSupportersBeforePublication()) {
    /** @var \app\models\supportTypes\CollectBeforePublish $supp */
    $supp = $form->motion->motionType->getAmendmentSupportTypeClass();
    $str  = \Yii::t('amend', 'support_collect_explanation');
    $str  = str_replace('%MIN%', $supp->getMinNumberOfSupporters(), $str);
    $str  = str_replace('%MIN+1%', ($supp->getMinNumberOfSupporters() + 1), $str);

    echo '<div style="font-weight: bold; text-decoration: underline;">' .
        \Yii::t('amend', 'support_collect_explanation_title') . '</div>' .
        $str . '<br><br>';
}

$amendmentPolicy = $form->motion->motionType->getAmendmentPolicy();
if (!in_array($amendmentPolicy::getPolicyID(), [IPolicy::POLICY_ALL, IPolicy::POLICY_LOGGED_IN])) {
    echo '<div>
                <legend>' . Yii::t('amend', 'amendment_requirement'), '</legend>
            </div>';

    echo $amendmentPolicy->getOnCreateDescription();
}

if (\Yii::$app->user->isGuest) {
    echo \app\components\AntiSpam::getJsProtectionHint($form->motion->id);
}

echo '<div id="draftHint" class="hidden alert alert-info" role="alert"
    data-motion-id="' . $form->motion->id . '" data-amendment-id="' . $form->amendmentId . '">' .
    \Yii::t('amend', 'unsaved_drafts') . '<ul></ul>
</div>

</div>';


echo Html::beginForm('', 'post', [
    'id'                        => 'amendmentEditForm',
    'class'                     => 'motionEditForm draftForm',
    'enctype'                   => 'multipart/form-data',
    'data-antragsgruen-widget'  => 'frontend/AmendmentEdit',
    'data-multi-paragraph-mode' => ($multipleParagraphs ? 1 : 0)
]);

echo '<h2 class="green">' . \Yii::t('amend', 'merge_new_text') . '</h2>';

if ($consultation->getSettings()->editorialAmendments || $consultation->getSettings()->globalAlternatives) {
    echo '<section class="editorialGlobalBar">';
    if ($consultation->getSettings()->globalAlternatives) {
        echo '<label>' . Html::checkbox('globalAlternative', $form->globalAlternative) .
            \Yii::t('amend', 'global_alternative') . '</label>';
    }
    if ($consultation->getSettings()->editorialAmendments) {
        echo '<label class="editorialChange">' . Html::checkbox('editorialChange', $form->editorial != '') .
            \Yii::t('amend', 'editorial_hint') . '</label>';
    }
    echo '</section>';
}

echo '<div class="content">';


if ($consultation->getSettings()->editorialAmendments) { ?>
    <div class="form-group wysiwyg-textarea hidden" id="sectionHolderEditorial"
         data-full-html="0" data-max-len="0">
        <label for="amendmentEditorial"><?= \Yii::t('amend', 'editorial_hint') ?></label>
        <textarea name="amendmentEditorial" id="amendmentEditorial"
                  class="raw"><?= Html::encode($form->editorial) ?></textarea>
        <div class="texteditor boxed" id="amendmentEditorial_wysiwyg"><?= $form->editorial ?></div>
    </div>
    <?php
}

foreach ($form->sections as $section) {
    echo $section->getSectionType()->getAmendmentFormField();
}

echo '</div>';


echo '<h2 class="green">' . \Yii::t('amend', 'reason') . '</h2>';

echo '<div class="content">';


echo '<div class="form-group wysiwyg-textarea" data-maxLen="0" data-fullHtml="0" id="amendmentReasonHolder">';
echo '<label for="amendmentReason">' . Yii::t('amend', 'reason') . '</label>';

echo '<textarea name="amendmentReason"  id="amendmentReason" class="raw">';
echo Html::encode($form->reason) . '</textarea>';
echo '<div class="texteditor boxed" id="amendmentReason_wysiwyg">';
echo $form->reason;
echo '</div>';
echo '</div>';

echo '</div>';


$initiatorClass = $form->motion->motionType->getAmendmentSupportTypeClass();
echo $initiatorClass->getAmendmentForm($form->motion->motionType, $form, $controller);


if (!$multipleParagraphs) {
    echo '<input type="hidden" name="modifiedSectionId" value="">';
    echo '<input type="hidden" name="modifiedParagraphNo" value="">';
}

echo '<div class="submitHolder content"><button type="submit" name="save" class="btn btn-primary">';
echo '<span class="glyphicon glyphicon-chevron-right"></span> ' . \Yii::t('amend', 'go_on');
echo '</button></div>';

echo Html::endForm();
