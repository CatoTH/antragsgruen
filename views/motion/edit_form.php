<?php

/**
 * @var \yii\web\View $this
 * @var string $mode
 * @var \app\models\forms\MotionEditForm $form
 * @var \app\models\db\Consultation $consultation
 * @var bool $forceTag
 */
use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\ConsultationSettingsTag;
use app\models\policies\IPolicy;
use yii\helpers\Html;

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

if ($mode == 'create') {
    $this->title = $form->motionType->createTitle;
} else {
    $this->title = str_replace('%TYPE%', $form->motionType->titleSingular, \Yii::t('motion', 'motion_edit'));
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

echo '<div class="form content hideIfEmpty">';

echo $controller->showErrors();

$publicPolicies = [IPolicy::POLICY_ALL, IPolicy::POLICY_LOGGED_IN, IPolicy::POLICY_WURZELWERK];
if (in_array($form->motionType->policyAmendments, $publicPolicies)) {
    echo '<div style="font-weight: bold; text-decoration: underline;">' .
        \Yii::t('motion', 'create_explanation_title') . '</div>' .
        str_replace('%HOME%', UrlHelper::homeUrl(), \Yii::t('motion', 'create_explanation')) .
        '<br><br>';
}
if ($form->motionType->getMotionSupportTypeClass()->collectSupportersBeforePublication()) {
    /** @var \app\models\supportTypes\CollectBeforePublish $supp */
    $supp = $form->motionType->getMotionSupportTypeClass();
    $str = \Yii::t('motion', 'support_collect_explanation');
    $str = str_replace('%MIN%', $supp->getMinNumberOfSupporters(), $str);
    $str = str_replace('%MIN+1%', ($supp->getMinNumberOfSupporters() + 1), $str);

    echo '<div style="font-weight: bold; text-decoration: underline;">' .
        \Yii::t('motion', 'support_collect_explanation_title') . '</div>' .
        $str . '<br><br>';
}


$motionPolicy = $form->motionType->getMotionPolicy();
if (!in_array($motionPolicy::getPolicyID(), $publicPolicies)) {
    echo '<div style="font-weight: bold; text-decoration: underline;">' .
        Yii::t('motion', 'create_prerequisites'), '</div>';

    echo $motionPolicy->getOnCreateDescription();
}

if (\Yii::$app->user->isGuest) {
    echo \app\components\AntiSpam::getJsProtectionHint($form->motionId);
}

echo '<div id="draftHint" class="hidden alert alert-info" role="alert"
    data-motion-type="' . $form->motionType->id . '" data-motion-id="' . $form->motionId . '">' .
    \Yii::t('amend', 'unsaved_drafts') . '<ul></ul>
</div>';

echo '</div>';


echo Html::beginForm('', 'post', [
    'id'                       => 'motionEditForm',
    'class'                    => 'motionEditForm draftForm',
    'enctype'                  => 'multipart/form-data',
    'data-antragsgruen-widget' => 'frontend/MotionEditForm'
]);

echo '<div class="content">';

/** @var ConsultationSettingsTag[] $tags */
$tags = array();
foreach ($consultation->tags as $tag) {
    $tags[$tag->id] = $tag;
}

if (count($tags) == 1) {
    $keys = array_keys($tags);
    echo '<input type="hidden" name="tags[]" value="' . $keys[0] . '" title="Tags">';
} elseif (count($tags) > 0) {
    echo '<div class="form-group">';
    if ($consultation->getSettings()->allowMultipleTags) {
        echo '<label class="legend">' . \Yii::t('motion', 'tag_tags') . '</label>';
        foreach ($tags as $id => $tag) {
            echo '<label class="checkbox-inline"><input name="tags[]" value="' . $id . '" type="checkbox" ';
            if (in_array($id, $form->tags)) {
                echo ' checked';
            }
            echo ' required title="Tags"> ' . Html::encode($tag->title) . '</label>';
        }
    } else {
        $layout->loadFuelux();
        $selected   = (count($form->tags) > 0 ? $form->tags[0] : 0);
        $tagOptions = [];
        foreach ($tags as $tag) {
            $tagOptions[$tag->id] = $tag->title;
        }
        echo '<label>' . \Yii::t('motion', 'tag_tags') . ':</label><div style="position: relative;">';
        echo HTMLTools::fueluxSelectbox('tags[]', $tagOptions, $selected, ['id' => 'tagSelect']);
        echo '</div>';
    }
    echo '</div>';
}

foreach ($form->sections as $section) {
    echo $section->getSectionType()->getMotionFormField();
}

echo '</div>';


$initiatorClass = $form->motionType->getMotionSupportTypeClass();
echo $initiatorClass->getMotionForm($form->motionType, $form, $controller);

echo '<div class="submitHolder content"><button type="submit" name="save" class="btn btn-primary">';
echo '<span class="glyphicon glyphicon-chevron-right"></span> ' . \Yii::t('motion', 'go_on');
echo '</button></div>';

echo Html::endForm();
