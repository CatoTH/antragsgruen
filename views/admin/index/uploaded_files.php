<?php

use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var array $files
 */

/** @var \app\controllers\admin\IndexController $controller */
$controller   = $this->context;
$consultation = $controller->consultation;
$layout       = $controller->layoutParams;
$layout->addCSS('css/backend.css');

$this->title = Yii::t('admin', 'files_title');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('/admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_consultation'), UrlHelper::createUrl('/admin/index/consultation'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_files'));

echo '<h1>' . Yii::t('admin', 'files_title') . '</h1>';
?>
<div class="content uploadedFilesManage">

    <ul class="files">
        <?php
        foreach ($files as $file) {
            ?>
            <li>
                <div>
                    <img src="<?= Html::encode($file->getUrl()) ?>" alt="<?= Html::encode($file->filename) ?>">
                </div>
                <?= Html::beginForm('', 'post', ['class' => 'deleteForm']) ?>
                <input type="hidden" name="id" value="<?= $file->id ?>">
                <button type="submit" name="delete" class="btn btn-link">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <?= Yii::t('pages', 'images_delete') ?>
                </button>
                <?= Html::endForm() ?>
            </li>
            <?php
        }
        ?>
    </ul>

    <?php
    if (count($files) === 0) {
        echo '<div class="noImages">' . Yii::t('admin', 'files_none') . '</div>';
    }
    ?>
</div>
