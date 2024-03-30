<?php

/**
 * @var \app\models\db\MotionSection $section
 */

use app\models\db\User;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use yii\helpers\Html;

$consultation   = $section->getConsultation();
$motion         = $section->getMotion();

if (User::havePrivilege($consultation, Privileges::PRIVILEGE_CHANGE_EDITORIAL, PrivilegeQueryContext::motion($motion))) {
    /** @var \app\controllers\MotionController $controller */
    $controller = $this->context;
    $controller->layoutParams->loadCKEditor();

    $saveUrl = \app\components\UrlHelper::createMotionUrl($motion, 'save-editorial', ['sectionId' => $section->sectionId]);
    echo Html::beginForm($saveUrl, 'post', [
        'class'                    => 'editorialEditForm',
        'data-antragsgruen-widget' => 'frontend/EditorialEdit',
    ]);

    echo '<button type="button" class="btn btn-sm btn-link editCaller">';
    echo '<span class="glyphicon glyphicon-edit" aria-hidden="true"></span> ';
    echo str_replace('%TYPE%', $motion->getMyMotionType()->titleSingular, Yii::t('motion', 'editorial_edit'));
    echo '</button><br>';
}

echo '<div class="textHolder stdPadding motionTextFormattings" id="section_' . $section->sectionId . '_content">';

echo $section->getData();

echo '</div>';


if (User::havePrivilege($consultation, Privileges::PRIVILEGE_CHANGE_EDITORIAL, PrivilegeQueryContext::motion($motion))) {
    echo '<div class="saveRow hidden">';
    echo '<button class="btn btn-primary submitBtn" type="submit">';
    echo Yii::t('base', 'save') . '</button></div>';

    echo Html::endForm();
}
