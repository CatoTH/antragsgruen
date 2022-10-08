<?php

use app\components\UrlHelper;
use app\models\policies\Nobody;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 * @var boolean $admin
 */

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
if ($admin) {
    $layout->loadCKEditor();
}

$consultation = UrlHelper::getCurrentConsultation();
$pageData     = \app\models\db\ConsultationText::getPageData($consultation->site, $consultation, 'feeds');
$this->title  = Yii::t('pages', 'content_feeds_title');

$hasComments   = false;
$hasMotions    = false;
$hasAmendments = false;

foreach ($consultation->motionTypes as $type) {
    if (!is_a($type->getCommentPolicy(), Nobody::class)) {
        $hasComments = true;
    }
    if (!is_a($type->getMotionPolicy(), Nobody::class)) {
        $hasMotions = true;
    }
    if (!is_a($type->getAmendmentPolicy(), Nobody::class)) {
        $hasAmendments = true;
    }
}

?>
<h1><?= Yii::t('pages', 'content_feeds_title') ?></h1>

<div class="content contentPage contentPageFeeds">
    <?php
    if ($admin) {
        $saveUrl = $pageData->getSaveUrl();
        echo Html::beginForm($saveUrl, 'post', [
            'data-upload-url'          => $pageData->getUploadUrl(),
            'data-image-browse-url'    => $pageData->getImageBrowseUrl(),
            'data-antragsgruen-widget' => 'frontend/ContentPageEdit',
            'data-text-selector'       => '#stdTextHolder',
            'data-save-selector'       => '.textSaver',
            'data-edit-selector'       => '.editCaller',
        ]);
        echo '<button type="button" class="btn btn-link editCaller">' . Yii::t('base', 'edit') . '</button><br>';
    }

    ?>
    <article class="textHolder" id="stdTextHolder">
        <?= $pageData->text ?>
    </article>
    <?php

    if ($admin) {
        echo '<div class="textSaver hidden">';
        echo '<button class="btn btn-primary" type="submit">';
        echo Yii::t('base', 'save') . '</button></div>';

        echo Html::endForm();
    }
    ?>
</div>

<h2 class="green">Feeds</h2>

<ul class="motionList motionListStd motionListWithoutAgenda">
    <?php
    $feeds = 0;

    if ($hasMotions) {
        $feeds++;
        ?>
        <li class="motion">
            <p class="title">
                <a href="<?= Html::encode(UrlHelper::createUrl('consultation/feedmotions')) ?>" class="feedMotions">
                    <span class="fontello fontello-rss-squared"></span>
                    <?= Yii::t('con', 'feed_motions') ?>
                </a>
            </p>
        </li>
        <?php
    }

    if ($hasAmendments) {
        $feeds++;
        ?>
        <li class="motion">
            <p class="title">
                <a href="<?= Html::encode(UrlHelper::createUrl('consultation/feedamendments')) ?>" class="feedAmendments">
                    <span class="fontello fontello-rss-squared"></span>
                    <?= Yii::t('con', 'feed_amendments') ?>
                </a>
            </p>
        </li>
        <?php
    }

    if ($hasComments) {
        $feeds++;
        ?>
        <li class="motion">
            <p class="title">
                <a href="<?= Html::encode(UrlHelper::createUrl('consultation/feedcomments')) ?>" class="feedComments">
                    <span class="fontello fontello-rss-squared"></span>
                    <?= Yii::t('con', 'feed_comments') ?>
                </a>
            </p>
        </li>
        <?php
    }

    if ($feeds > 1) {
        ?>
        <li class="motion">
            <p class="title">
                <a href="<?= Html::encode(UrlHelper::createUrl('consultation/feedall')) ?>" class="feedAll">
                    <span class="fontello fontello-rss-squared"></span>
                    <?= Yii::t('con', 'feed_all') ?>
                </a>
            </p>
        </li>
        <?php
    }

    ?>
</ul>

