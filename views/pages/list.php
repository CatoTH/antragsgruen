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

$this->title = \Yii::t('pages', 'list_title');

$defaultPages = ConsultationText::getDefaultPages();
$pages        = ConsultationText::getAllPages($controller->site, $controller->consultation);
$foundPageIds = [];
?>
<h1><?= $this->title = \Yii::t('pages', 'list_title'); ?></h1>

<div class="content">
    <?php
    if (count($pages) > 0) {
        ?>
        <strong><?= \Yii::t('pages', 'list_edit') ?></strong>
        <ul>
            <?php
            foreach ($pages as $page) {
                $title = $page->title . ' (' . $page->textId . ')';
                echo '<li>' . Html::a(Html::encode($title), $page->getUrl()) . '</li>';
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
        <strong><?= \Yii::t('pages', 'list_add') ?></strong>
        <ul>
            <?php
            foreach ($missing as $textId => $title) {
                $params = ['pages/show-page', 'pageSlug' => $textId];
                if (!in_array($textId, ConsultationText::getSitewidePages())) {
                    $params['consultationPath'] = $controller->consultation->urlPath;
                }
                $url = \app\components\UrlHelper::createUrl($params);
                echo '<li>' . Html::a(Html::encode($title . ' (' . $textId . ')'), $url) . '</li>';
            }
            ?>
        </ul>
        <?php
    }
    ?>
</div>
