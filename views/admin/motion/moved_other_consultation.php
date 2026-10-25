<?php

use app\components\UrlHelper;

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var \app\models\db\Motion $newMotion
 */


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
