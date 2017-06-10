<?php
use yii\helpers\Html;

/**
 * @var \app\models\forms\AntragsgruenInitSite $model
 * @var \Callable $t
 */

?>
<div class="step-pane active" id="panelSiteData" data-tab="stepSite">
    <fieldset class="siteData">
        <legend><?= $t('sitedate_title') ?></legend>
        <div class="description"><?= $t('sitedate_desc') ?></div>

        <div class="row firstrow">
            <div class="col-md-6 col-md-offset-3">

                <div class="form-group has-feedback">
                    <label class="name" for="siteTitle"><?= $t('sitedata_sitetitle') ?>:</label>
                    <?= Html::input(
                        'text',
                        'SiteCreateForm[title]',
                        $model->title,
                        ['id' => 'siteTitle', 'class' => 'form-control', 'required' => 'required']
                    ); ?>
                    <span class="error glyphicon glyphicon-remove form-control-feedback" aria-hidden="true"></span>
                    <span class="success glyphicon glyphicon-ok form-control-feedback" aria-hidden="true"></span>
                </div>

                <div class="form-group">
                    <label class="name" for="siteTitle"><?= $t('sitedata_system_email') ?>:</label>
                    <?= Html::input(
                        'email',
                        'SiteCreateForm[siteEmail]',
                        $model->siteEmail,
                        ['id' => 'siteEmail', 'class' => 'form-control']
                    ); ?>
                </div>

                <div class="form-group">
                    <label>
                        <?= Html::checkbox('SiteCreateForm[prettyUrls]', $model->prettyUrls, ['id' => 'prettyUrls']) ?>
                        <?= $t('sitedata_prettyurl') ?>
                    </label>
                </div>

                <div class="contactRow">
                    <label>
                        <strong><?= $t('sitedata_contact') ?>:</strong><br>
                        <small><?= $t('sitedata_contact_hint') ?></small>
                        <?= Html::textarea(
                            'SiteCreateForm[contact]',
                            $model->contact,
                            ['rows' => 5, 'required' => 'required', 'id' => 'siteContact', 'class' => 'form-control']
                        ) ?>
                    </label>

                </div>
            </div>
        </div>
    </fieldset>
    <div class="navigation">
        <button class="btn btn-lg btn-prev"><span class="icon-chevron-left"></span> <?= $t('prev') ?></button>
        <button type="submit" class="btn btn-lg btn-next btn-primary" name="create">
            <span class="icon-chevron-right"></span> <?= $t('finish') ?>
        </button>
    </div>
</div>