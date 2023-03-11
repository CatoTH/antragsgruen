<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Motion $motion
 * @var string $mode
 * @var \app\controllers\Base $controller
 */

$typeName = $motion->getMyMotionType()->titleSingular;
if ($mode === 'create') {
    $this->title = $typeName;
} else {
    $this->title = str_replace('%TYPE%', $typeName, Yii::t('motion', 'motion_edit'));
}

$controller = $this->context;
$controller->layoutParams->addBreadcrumb($this->title, UrlHelper::createMotionUrl($motion));

echo '<h1>' . str_replace('%TITLE%', $typeName, Yii::t('motion', 'created_statutes')) . '</h1>';
$controller->layoutParams->addBreadcrumb(Yii::t('motion', 'created_statutes'));

$backUrl = UrlHelper::createUrl(['/admin/motion-type/type', 'motionTypeId' => $motion->getMyMotionType()->id])
?>

<div class="content" id="motionConfirmedForm">
    <div class="alert alert-success" role="alert">
        <?= Yii::t('motion', 'created_statutes_done') ?>
    </div>

    <p class="btnRow">
        <a href="<?= Html::encode($backUrl) ?>" class="btn btn-success btnBack"><?= Yii::t('motion', 'back_start') ?></a>
    </p>
</div>
