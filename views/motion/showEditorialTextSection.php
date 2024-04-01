<?php

/**
 * @var \app\models\db\MotionSection $section
 */

use app\models\db\User;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use yii\helpers\Html;

$consultation = $section->getConsultation();
$motion = $section->getMotion();
/** @var \app\models\sectionTypes\TextEditorial $sectionType */
$sectionType = $section->getSectionType();

$metadataView = '<div class="metadataView">';
$metadataView .= $sectionType->getFormattedSectionMetadata(true);
$metadataView .= '</div>';

if (User::havePrivilege($consultation, Privileges::PRIVILEGE_CHANGE_EDITORIAL, PrivilegeQueryContext::motion($motion))) {
    /** @var \app\controllers\MotionController $controller */
    $controller = $this->context;
    $controller->layoutParams->loadCKEditor();
    $metadata = $sectionType->getSectionMetadata();

    $saveUrl = \app\components\UrlHelper::createMotionUrl($motion, 'save-editorial', ['sectionId' => $section->sectionId]);
    echo Html::beginForm($saveUrl, 'post', [
        'class'                    => 'editorialEditForm',
        'data-antragsgruen-widget' => 'frontend/EditorialEdit',
    ]);

    ?>
    <div class="editorialHeader toolbarBelowTitle">

        <?= $metadataView ?>

        <div class="metadataEdit hidden">
            <label>
                <span><?= Yii::t('motion', 'editorial_author') ?>:</span>
                <input type="text" name="author" class="form-control author" value="<?= Html::encode($metadata['author'] ?? '') ?>">
            </label>
            <label>
                <input type="checkbox" name="updateDate" class="updateDate" checked>
                <?= Yii::t('motion', 'editorial_update_date') ?>
            </label>
        </div>

        <button type="button" class="btn btn-sm btn-link editCaller">
            <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
            <?= str_replace('%TYPE%', $motion->getMyMotionType()->titleSingular, Yii::t('motion', 'editorial_edit')) ?>
        </button>
    </div>
    <?php
} else {
    echo '<div class="toolbarBelowTitle">' . $metadataView . '</div>';
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
