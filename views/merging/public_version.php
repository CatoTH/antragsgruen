<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\mergeAmendments\Draft;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Motion $motion
 * @var Draft $draft
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;

$layout->robotsNoindex = true;
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(Yii::t('amend', 'merge_bread'));
$layout->loadBootstrapToggle();

$title       = str_replace('%TITLE%', $motion->getMyMotionType()->titleSingular, Yii::t('amend', 'merge_title'));
$this->title = $title . ': ' . $motion->getTitleWithPrefix();

?>
    <h1 class="stickyHeader"><?= Html::encode($motion->getTitleWithPrefix()) ?></h1>
<?php
if ($consultation->getSettings()->hasSpeechLists) {
    echo $this->render('@app/views/speech/_footer_widget', ['queue' => $motion->getActiveSpeechQueue()]);
}
?>
    <div class="motionData content">
        <table class="motionDataTable">
            <tr>
                <th><?= Yii::t('motion', 'consultation') ?>:</th>
                <td><?= Html::a(Html::encode($motion->getMyConsultation()->title), UrlHelper::createUrl('consultation/index')) ?></td>
            </tr>
            <tr>
                <th><?= Html::encode($motion->getMyMotionType()->titleSingular) ?>:</th>
                <td><?= Html::a(Html::encode($motion->getTitleWithPrefix()), UrlHelper::createMotionUrl($motion)) ?></td>
            </tr>
            <tr>
                <th><?= Yii::t('amend', 'merge_draft_date') ?></th>
                <td class="mergeDraftDate"><?= \app\components\Tools::formatMysqlDateTime($draft->time->format('Y-m-d H:i:s')) ?></td>
            </tr>
        </table>
    </div>
    <section class="motionTextHolder mergePublicDraft"
             data-reload-url="<?= Html::encode(UrlHelper::createMotionUrl($motion, 'merge-amendments-public-ajax')) ?>"
             data-antragsgruen-widget="frontend/MotionMergeAmendmentsPublic">
        <h2 class="green"><?= Yii::t('amend', 'merge_new_text') ?></h2>
        <div class="content">
            <div class="header">
                <div class="motionUpdateInfo">
                    <div class="alert alert-info" role="alert"><?= Yii::t('motion', 'merging_draft_warning') ?></div>
                </div>
                <div class="motionUpdateWidget">
                    <div>
                        <button class="btn btn-sm btn-default" type="button" title="<?= Yii::t('amend', 'merge_draft_fullscreen') ?>"
                                data-antragsgruen-widget="frontend/FullscreenToggle">
                            <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
                            <span class="sr-only"><?= Yii::t('amend', 'merge_draft_fullscreen') ?></span>
                        </button>

                        <button id="updateBtn" class="btn btn-sm btn-default" type="button" title="<?= Yii::t('amend', 'merge_draft_reload') ?>">
                            <span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
                            <span class="sr-only"><?= Yii::t('amend', 'merge_draft_reload') ?></span>
                        </button>

                        <label class="sr-only" for="autoUpdateToggle"><?= Yii::t('amend', 'merge_draft_auto_update') ?></label>
                        <input type="checkbox" id="autoUpdateToggle" checked
                               data-onstyle="success" data-size="small" data-toggle="toggle"
                               data-on="<?= Html::encode(Yii::t('amend', 'merge_draft_auto_update')) ?>"
                               data-off="<?= Html::encode(Yii::t('amend', 'merge_draft_auto_update')) ?>">
                    </div>
                    <div class="updated">
                        <span class="glyphicon glyphicon-ok"></span>
                        <?= Yii::t('amend', 'merge_draft_updated') ?>
                    </div>
                </div>
            </div>

            <div class="draftContent motionMergeStyles">
                <?= $this->render('_public_version_content', ['motion' => $motion, 'draft' => $draft]) ?>
            </div>
        </div>
    </section>
<?php
