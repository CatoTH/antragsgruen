<?php

use app\components\UrlHelper;
use app\models\settings\Privileges;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var \app\models\db\Consultation $consultation
 */

$welcomeReplace = \app\models\layoutHooks\Layout::getConsultationWelcomeReplacer();
if ($welcomeReplace !== null) {
    echo $welcomeReplace;
    return;
}


$contentAdmin = User::havePrivilege($consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null);

$preWelcome = \app\models\layoutHooks\Layout::getConsultationPreWelcome();

echo '<div class="content contentPage contentPageWelcome' . ($preWelcome ? ' hasDeadline' : '') . '">';

echo $this->render('@app/views/shared/translate', ['toTranslateUrl' => UrlHelper::homeUrl()]);

echo $preWelcome;

$pageData = \app\models\db\ConsultationText::getPageData($consultation->site, $consultation, 'welcome');
$saveUrl  = $pageData->getSaveUrl();

if ($contentAdmin) {
    echo Html::beginForm($saveUrl, 'post', [
        'data-upload-url'          => $pageData->getUploadUrl(),
        'data-image-browse-url'    => $pageData->getImageBrowseUrl(),
        'data-file-delete-url'     => $pageData->getFileDeleteUrl(),
        'data-del-confirmation'    => Yii::t('admin', 'files_download_del_c'),
        'data-antragsgruen-widget' => 'frontend/ContentPageEdit',
        'data-text-selector'       => '#stdTextHolder',
        'data-save-selector'       => '.textSaver',
        'data-edit-selector'       => '.editCaller',
    ]);
    echo '<button type="button" class="btn btn-sm btn-link editCaller">';
    echo '<span class="glyphicon glyphicon-edit" aria-hidden="true"></span> ';
    echo Yii::t('con', 'edit_welcome');
    echo '</button><br>';
}

echo '<article class="textHolder" id="stdTextHolder">';
echo $pageData->text;
echo '</article>';

$files = $consultation->getDownloadableFiles($pageData->getMyFileGroup()?->id);
if ($pageData->getMyFileGroup()) {
    // Legacy way of storing files (fileGroupId = null)
    $files = array_merge($consultation->getDownloadableFiles(null), $files);
}
echo $this->render('@app/views/pages/_content_files', [
    'contentAdmin' => $contentAdmin,
    'files' => $files,
]);

if ($contentAdmin) {
    ?>
    <div class="textSaver hidden">
        <button class="btn btn-primary" type="submit">
            <?= Yii::t('base', 'save') ?>
        </button>
    </div>
    <?php
    echo Html::endForm();
}

echo '</div>';
