<?php

/**
 * @var Yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\MotionEditForm $form
 * @var \app\models\db\Consultation $consultation
 * @var bool $forceTag
 */

use app\components\UrlHelper;
use app\models\policies\IPolicy;
use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$isAmendmentsOnly = !!$form->motionType->amendmentsOnly;

if ($isAmendmentsOnly) {
    $this->title = $form->motionType->titleSingular . ': ' . Yii::t('motion', 'statutes_base_head');
} elseif ($mode === 'create') {
    $this->title = $form->motionType->createTitle;
} else {
    $this->title = str_replace('%TYPE%', $form->motionType->titleSingular, Yii::t('motion', 'motion_edit'));
}
$layout->robotsNoindex = true;

$layout->loadCKEditor();
$layout->loadDatepicker();

$layout->addBreadcrumb($this->title);

if ($form->agendaItem) {
    echo '<h1>' . Html::encode($form->agendaItem->title . ': ' . $this->title) . '</h1>';
} else {
    echo '<h1>' . Html::encode($this->title) . '</h1>';
}

echo $controller->showErrors();

echo '<div class="form content hideIfEmpty motionEditPreface">';

$publicPolicies = [IPolicy::POLICY_ALL, IPolicy::POLICY_LOGGED_IN, IPolicy::POLICY_GRUENES_NETZ];
if (!$isAmendmentsOnly) {
    echo str_replace('%HOME%', UrlHelper::homeUrl(), $form->motionType->getConsultationTextWithFallback('motion', 'create_explanation')) . '<br><br>';
}

if ($form->motionType->getMotionSupportTypeClass()->collectSupportersBeforePublication()) {
    /** @var \app\models\supportTypes\CollectBeforePublish $supp */
    $supp = $form->motionType->getMotionSupportTypeClass();
    $str = $form->motionType->getConsultationTextWithFallback('motion', 'support_collect_explanation');
    $str = str_replace('%MIN%', $supp->getSettingsObj()->minSupporters, $str);
    $str = str_replace('%MIN+1%', ($supp->getSettingsObj()->minSupporters + 1), $str);
    $title = $form->motionType->getConsultationTextWithFallback('motion', 'support_collect_explanation_title');

    if (trim($title) !== '' || trim($str) !== '') {
        echo '<div style="font-weight: bold; text-decoration: underline;">' .
             Yii::t('motion', 'support_collect_explanation_title') . '</div>' .
             $str . '<br><br>';
    }
}


$motionPolicy = $form->motionType->getMotionPolicy();
if (!in_array($motionPolicy::getPolicyID(), $publicPolicies) && !$isAmendmentsOnly) {
    echo '<div style="font-weight: bold; text-decoration: underline;">' .
        Yii::t('motion', 'create_prerequisites'), '</div>';

    echo $motionPolicy->getOnCreateDescription();
}

if (!\app\models\db\User::getCurrentUser()) {
    echo \app\components\AntiSpam::getJsProtectionHint((string)$form->motionId);
}

echo '<div id="draftHint" class="hidden alert alert-info"
    data-motion-type="' . $form->motionType->id . '" data-motion-id="' . $form->motionId . '">' .
    Yii::t('amend', 'unsaved_drafts') . '<ul></ul>
</div>';

echo '</div>';


echo Html::beginForm('', 'post', [
    'id'                       => 'motionEditForm',
    'class'                    => 'motionEditForm draftForm',
    'enctype'                  => 'multipart/form-data',
    'data-antragsgruen-widget' => 'frontend/MotionEditForm'
]);

echo '<div class="content">';

if (count($form->motionType->agendaItems) > 0 && !$isAmendmentsOnly) {
    echo '<fieldset class="form-group">';
    echo '<legend class="legend">' . Yii::t('motion', 'agenda_item') . '</label>';
    if ($form->agendaItem) {
        echo '<div>' . Html::encode($form->agendaItem->title) . '</div>';
    } else {
        echo '<div style="position: relative;">';
        $agendaItems = [];
        foreach ($form->motionType->agendaItems as $agendaItem) {
            $agendaItems[$agendaItem->id] = $agendaItem->title;
        }
        echo Html::dropDownList('agendaItem', null, $agendaItems, ['id' => 'agendaSelect', 'class' => 'stdDropdown']);
        echo '</div>';
    }
    echo '</fieldset>';
}


if (!$isAmendmentsOnly) {
    echo $this->render('@app/views/shared/edit_tags', ['consultation' => $consultation, 'tagIds' => $form->tags]);
}

foreach ($form->sections as $section) {
    echo $section->getSectionType()->getMotionFormField();
}

echo '</div>';


if ($form->getAllowEditinginitiators()) {
    $initiatorClass = $form->motionType->getMotionSupportTypeClass();
    echo $initiatorClass->getMotionForm($form->motionType, $form, $controller);
}

if ($isAmendmentsOnly) {
    $backUrl = UrlHelper::createUrl(['/admin/motion-type/type', 'motionTypeId' => $form->motionType->id]);
} else {
    $backUrl = UrlHelper::homeUrl();
}
?>
<section class="content saveCancelRow">
    <div class="saveCol">
        <button type="submit" name="save" class="btn btn-primary">
            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
            <?= Yii::t('motion', 'go_on') ?>
        </button>
    </div>
    <div class="cancelCol">
        <a href="<?= Html::encode($backUrl) ?>" id="cancel" class="btn">
            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
            <?= Yii::t('motion', 'back_start') ?>
        </a>
    </div>
</section>

<?php
echo Html::endForm();
