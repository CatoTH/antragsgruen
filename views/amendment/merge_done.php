<?php

use app\components\UrlHelper;
use app\models\db\{Amendment, Motion};
use app\components\Html;

/**
 * @var \app\components\view\View $this
 * @var \app\models\settings\Layout $layout
 * @var \app\models\db\Consultation|null $consultation
 * @var Amendment $amendment
 * @var Motion $newMotion
 */

$this->title = Yii::t('amend', 'merge1_done_title');


$layout->addBreadcrumb($newMotion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($newMotion));
if (!$consultation->getSettings()->hideTitlePrefix && $amendment->getFormattedTitlePrefix() != '') {
    $layout->addBreadcrumb($amendment->getFormattedTitlePrefix(), UrlHelper::createAmendmentUrl($amendment));
} else {
    $layout->addBreadcrumb(Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment));
}
$layout->addBreadcrumb(Yii::t('amend', 'merge1_title'));

?>

<h1><?= Yii::t('amend', 'merge1_done_title') ?></h1>

<div class="content">
    <div class="alert alert-success">
        <?= Yii::t('amend', 'merge1_done_str') ?>

        <div style="text-align: center; margin-top: 20px;"><?php
            echo Html::a(
                Yii::t('amend', 'merge1_done_goto'),
                UrlHelper::createMotionUrl($newMotion),
                ['class' => 'btn btn-primary']
            );
            ?>
        </div>
    </div>
</div>
