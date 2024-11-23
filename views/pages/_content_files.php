<?php

use app\models\db\ConsultationFile;
use yii\helpers\Html;

/**
 * @var ConsultationFile[] $files
 * @var bool $contentAdmin
 */

?>
    <div class="downloadableFiles<?= (count($files) === 0 ? ' hidden' : '') ?>">
        <h2><?= Yii::t('admin', 'files_download') ?></h2>
        <em class="none<?= (count($files) > 0 ? ' hidden' : '') ?>"><?= Yii::t('admin', 'files_download_none') ?></em>
        <?php
        echo '<ul class="fileList">';
        foreach ($files as $file) {
            echo '<li data-id="' . Html::encode($file->id) . '">';
            $title = '<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span> <span class="title">' . Html::encode($file->title) . '</span>';
            echo \app\components\HTMLTools::createExternalLink($title, $file->getUrl());
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
