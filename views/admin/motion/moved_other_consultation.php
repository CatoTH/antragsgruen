<?php

use app\components\UrlHelper;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Motion $newMotion
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('admin', 'motion_moved_title');
$layout->addBreadcrumb(Yii::t('admin', 'bread_list'), UrlHelper::createUrl('admin/motion-list/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_move'));

echo '<h1>' . Yii::t('admin', 'motion_moved_title') . '</h1>';

$relative = UrlHelper::createMotionUrl($newMotion);
$url      = '<a href="' . $relative . '">' . UrlHelper::absolutizeLink($relative) . '</a>';
$txt      = str_replace('%URL%', $url, Yii::t('admin', 'motion_moved_text'));

?>
<div class="content">
    <div class="alert alert-success">
        <?= $txt ?>
    </div>
</div>
