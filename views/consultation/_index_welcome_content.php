<?php

use app\components\Tools;
use app\models\db\ConsultationMotionType;
use yii\helpers\Html;

echo '<div class="content contentPage contentPageWelcome">';

if (count($consultation->motionTypes) === 1) {
    $deadline = $consultation->motionTypes[0]->getUpcomingDeadline(ConsultationMotionType::DEADLINE_MOTIONS);
    if ($deadline) {
        echo '<p class="deadlineCircle">' . Yii::t('con', 'deadline_circle') . ': ';
        echo Tools::formatMysqlDateTime($deadline) . "</p>\n";
    }
}

$pageData = \app\models\db\ConsultationText::getPageData($consultation->site, $consultation, 'welcome');
$saveUrl  = $pageData->getSaveUrl();
if ($admin) {
    echo Html::beginForm($saveUrl, 'post', [
        'data-upload-url'          => $pageData->getUploadUrl(),
        'data-image-browse-url'    => $pageData->getImageBrowseUrl(),
        'data-antragsgruen-widget' => 'frontend/ContentPageEdit',
    ]);
    echo '<a href="#" class="editCaller">' . Yii::t('base', 'edit') . '</a><br>';
}

echo '<article class="textHolder" id="stdTextHolder">';
echo $pageData->text;
echo '</article>';

$files = $consultation->getDownloadableFiles();
?>
    <div class="downloadableFiles <?= (count($files) === 0 ? 'hidden' : '') ?>">
        <h2><?= Yii::t('admin', 'files_download') ?></h2>
        <?php
        if (count($files) === 0) {
            echo '<em class="none">' . Yii::t('admin', 'files_download_none') . '</em>';
        }
        echo '<ul class="fileList">';
        foreach ($files as $file) {
            echo '<li>';
            $title = '<span class="glyphicon glyphicon-download-alt"></span> ' . Html::encode($file->title);
            echo Html::a($title, $file->getUrl());
            echo '</li>';
        }
        echo '</ul>';
        if ($admin) {
            ?>
            <div class="downloadableFilesUpload hidden">
                <h3><?= Yii::t('admin', 'files_download_new') ?>:</h3>
                <div class="uploadCol">
                    <input type="file" id="downloadableFileNew">
                    <label for="downloadableFileNew">
                        <span class="glyphicon glyphicon-upload"></span>
                        <span class="text" data-title="<?= Html::encode(Yii::t('admin', 'files_download_file')) ?>">
                            <?= Yii::t('admin', 'files_download_file') ?>
                        </span>
                    </label>
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

if ($admin) {
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
