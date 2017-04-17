<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var Motion $draft
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$layout->robotsNoindex = true;
$layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
$layout->addBreadcrumb(\Yii::t('amend', 'merge_bread'));
$layout->loadFuelux();
$layout->loadBootstrapToggle();

$title       = str_replace('%TITLE%', $motion->motionType->titleSingular, \Yii::t('amend', 'merge_title'));
$this->title = $title . ': ' . $motion->getTitleWithPrefix();

?>
    <h1><?= Html::encode($motion->getTitleWithPrefix()) ?></h1>

    <div class="motionData content">
        <table class="motionDataTable">
            <tr>
                <th><?= Yii::t('motion', 'consultation') ?>:</th>
                <td><?= Html::a($motion->getMyConsultation()->title, UrlHelper::createUrl('consultation/index')) ?></td>
            </tr>
            <tr>
                <th><?= Html::encode($motion->motionType->titleSingular) ?>:</th>
                <td><?= Html::a($motion->getTitleWithPrefix(), UrlHelper::createMotionUrl($motion)) ?></td>
            </tr>
            <tr>
                <th><?= \Yii::t('amend', 'merge_draft_date') ?></th>
                <td class="mergeDraftDate"><?= \app\components\Tools::formatMysqlDateTime($draft->dateCreation) ?></td>
            </tr>
        </table>
    </div>
    <section class="motionTextHolder mergePublicDraft"
             data-reload-url="<?= Html::encode(UrlHelper::createMotionUrl($motion, 'merge-amendments-public-ajax')) ?>"
             data-antragsgruen-widget="frontend/MotionMergeAmendmentsPublic">
        <h2 class="green"><?= \Yii::t('amend', 'merge_new_text') ?></h2>
        <div class="content">
            <div class="row">
                <div class="col-md-9">
                    <div class="alert alert-info" role="alert"><?= \Yii::t('motion', 'merging_draft_warning') ?></div>
                </div>
                <div class="col-md-3 motionUpdateWidget">
                    <div>
                        <button id="updateBtn" class="btn btn-sm btn-default">
                            <span class="glyphicon glyphicon-refresh"></span>
                        </button>

                        <label class="sr-only" for="autoUpdateToggle"></label>
                        <input type="checkbox" id="autoUpdateToggle" checked
                               data-onstyle="success" data-size="small" data-toggle="toggle"
                               data-on="<?= Html::encode(\Yii::t('amend', 'merge_draft_auto_update')) ?>"
                               data-off="<?= Html::encode(\Yii::t('amend', 'merge_draft_auto_update')) ?>">
                    </div>
                    <div class="updated">
                        <span class="glyphicon glyphicon-ok"></span>
                        <?=\Yii::t('amend', 'merge_draft_updated')?>
                    </div>
                </div>
            </div>

            <div class="draftContent motionMergeStyles">
                <?= $this->render('_merge_amendments_public', ['motion' => $motion, 'draft' => $draft]) ?>
            </div>
        </div>
    </section>
<?php
