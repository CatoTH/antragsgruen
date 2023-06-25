<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 * @var Amendment $amendment
 * @var Amendment[] $collidingAmendments
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('amend', 'merge1_err_collision');

$motion    = $amendment->getMyMotion();
$motionUrl = UrlHelper::createMotionUrl($motion);
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), $motionUrl);
if (!$consultation->getSettings()->hideTitlePrefix && $amendment->getFormattedTitlePrefix() != '') {
    $layout->addBreadcrumb($amendment->getFormattedTitlePrefix(), UrlHelper::createAmendmentUrl($amendment));
} else {
    $layout->addBreadcrumb(Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment));
}
$layout->addBreadcrumb(Yii::t('amend', 'merge1_title'));


?>
<h1><?= Yii::t('amend', 'merge1_err_collision') ?></h1>

<div class="content">
    <div class="alert alert-danger">
        <?= Yii::t('amend', 'merge1_err_collision_desc') ?>
        <ul>
            <?php foreach ($collidingAmendments as $collidingAmendment) { ?>
                <li>
                    <a href="<?= Html::encode(UrlHelper::createAmendmentUrl($collidingAmendment)) ?>">
                        <?= Html::encode($collidingAmendment->getTitle()) ?>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </div>
</div>
