<?php

/**
 * @var $this yii\web\View
 * @var string $pageKey
 * @var string $saveUrl
 * @var bool $admin
 */

use app\models\db\ConsultationText;
use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$this->title = Yii::t('pages', 'list_title');

$defaultPages = ConsultationText::getDefaultPages();
$pages        = ConsultationText::getAllPages($controller->site, $controller->consultation);
$foundPageIds = [];
?>
<h1><?= $this->title = Yii::t('pages', 'list_title'); ?></h1>

<div class="content">
    <?php
    echo $controller->showErrors();

    if (count($pages) > 0) {
        ?>
        <strong><?= Yii::t('pages', 'list_edit') ?></strong>
        <ul>
            <?php
            foreach ($pages as $page) {
                $title   = $page->title . ' (' . $page->textId . ')';
                $options = ['class' => 'editPage ' . $page->textId];
                echo '<li>' . Html::a(Html::encode($title), $page->getUrl(), $options) . '</li>';
                $foundPageIds[] = $page->textId;
            }
            ?>
        </ul>
        <?php
    }
    $missing = [];
    foreach ($defaultPages as $key => $title) {
        if (!in_array($key, $foundPageIds)) {
            $missing[$key] = $title;
        }
    }
    if (count($missing) > 0) {
        ?>
        <strong><?= Yii::t('pages', 'list_add_std') ?></strong>
        <ul>
            <?php
            foreach ($missing as $textId => $title) {
                if ($textId === 'feeds') {
                    $url     = \app\components\UrlHelper::createUrl(['consultation/feeds']);
                    $options = [];
                } else {
                    $params = ['pages/show-page', 'pageSlug' => $textId];
                    if (!in_array($textId, ConsultationText::getSitewidePages())) {
                        $params['consultationPath'] = $controller->consultation->urlPath;
                    }
                    $options = ['class' => 'createPage ' . $textId];
                    $url     = \app\components\UrlHelper::createUrl($params);
                }
                echo '<li>' . Html::a(Html::encode($title . ' (' . $textId . ')'), $url, $options) . '</li>';
            }
            ?>
        </ul>
        <?php
    }
    ?>
</div>
<br>
<?= Html::beginForm('', 'post', [
    'class'                    => 'createPageForm form-inline',
    'data-antragsgruen-widget' => 'frontend/ContentPageCreate',
]) ?>
<h2 class="green"><?= Yii::t('pages', 'list_add_custom') ?></h2>
<div class="content">
    <div class="form-group">
        <label for="contentUrl"><?= Yii::t('pages', 'settings_url') ?>:</label>
        <input type="text" class="form-control" name="url" value=""
               required id="contentUrl">
    </div>
    <div class="form-group">
        <label for="contentTitle"><?= Yii::t('pages', 'settings_title') ?>:</label>
        <input type="text" class="form-control" name="title" value=""
               required id="contentTitle" maxlength="30">
    </div>
    <label class="form-group">
        <?= Html::checkbox('inMenu', true) ?>
        <?= Yii::t('pages', 'settings_inmenu') ?>
    </label>
    <div class="form-group">
        <button type="submit" class="btn btn-primary" name="create" value="create">
            <?= Yii::t('pages', 'create_btn') ?>
        </button>
    </div>
</div>
<?= Html::endForm() ?>
