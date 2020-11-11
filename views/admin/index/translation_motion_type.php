<?php

/**
 * @var $this yii\web\View
 * @var ConsultationMotionType $motionType
 * @var string $category
 */

use app\models\db\ConsultationMotionType;
use app\components\{HTMLTools, yii\MessageSource, UrlHelper};
use yii\helpers\Html;
use yii\i18n\I18N;

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout = $controller->layoutParams;

$layout->addCSS('css/backend.css');

$this->title = Yii::t('admin', 'translating_motion_type') . ': ' . $motionType->titlePlural;
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'bread_language'));
$layout->bodyCssClasses[] = 'adminTranslationForm';

?>

<h1><?= Html::encode($this->title) ?></h1>
<div class="content">

    <div class="alert alert-info"><?= Yii::t('admin', 'translating_motion_hint') ?></div>

    <?php
    foreach ($motionType->consultationTexts as $consultationText) {
        echo $consultationText->title;
    }
    ?>
</div>
