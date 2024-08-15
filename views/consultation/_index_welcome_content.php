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
    echo '<div class="content contentPage contentPageWelcome">';
    echo '<article class="textHolder" id="stdTextHolder">';
    echo $welcomeReplace;
    echo '</article>';
    echo '</div>';
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

$files = $consultation->getDownloadableFiles(null);
?>
    <div class="downloadableFiles<?= (count($files) === 0 ? ' hidden' : '') ?>">
        <h2><?= Yii::t('admin', 'files_download') ?></h2>
        <em class="none<?= (count($files) > 0 ? ' hidden' : '') ?>"><?= Yii::t('admin', 'files_download_none') ?></em>
        <?php
        echo '<ul class="fileList">';
        foreach ($files as $file) {
            echo '<li data-id="' . Html::encode($file->id) . '">';
            $title = '<span class="glyphicon glyphicon-download-alt"></span> <span class="title">' . Html::encode($file->title) . '</span>';
            echo Html::a($title, $file->getUrl());
            if ($contentAdmin) {
                echo '<button type="button" class="btn btn-link deleteFile">';
                echo '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>';
                echo '<span class="sr-only">' . str_replace('%TITLE%', $file->title, Yii::t('admin', 'files_download_del')) . '</span>';
                echo '</button>';
            }
            echo '</li>';
        }
        echo '</ul>';
        if ($contentAdmin) {
            ?>
            <div class="downloadableFilesUpload hidden">
                <h3><?= Yii::t('admin', 'files_download_new') ?>:</h3>
                <div class="uploadCol">
                    <label for="downloadableFileNew">
                        <span class="glyphicon glyphicon-upload" aria-hidden="true"></span>
                        <span class="text" data-title="<?= Html::encode(Yii::t('admin', 'files_download_file')) ?>">
                            <?= Yii::t('admin', 'files_download_file') ?>
                        </span>
                    </label>
                    <input type="file" id="downloadableFileNew">
                </div>
                <div class="titleCol">
                    <input type="text" id="downloadableFileTitle" class="form-control"
                           placeholder="<?= Html::encode(Yii::t('admin', 'files_download_title')) ?>"
                           title="<?= Html::encode(Yii::t('admin', 'files_download_title')) ?>">
                </div>
            </div>
            <?php
        }
        ?>
    </div>
<?php

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
