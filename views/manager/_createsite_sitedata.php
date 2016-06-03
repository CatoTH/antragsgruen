<?php
use app\models\forms\SiteCreateForm2;
use yii\helpers\Html;

/**
 * @var SiteCreateForm2 $model
 * @var \Callable $t
 */

?>
<div class="step-pane active" id="panelSiteData" data-tab="stepSite">
    <fieldset class="siteData">
        <legend><?= $t('sitedate_title') ?></legend>
        <div class="description"><?= $t('sitedate_desc') ?></div>

        <div class="row firstrow">
            <div class="col-md-6 col-md-offset-3">

                <div class="form-group">
                    <label class="name" for="siteTitle"><?= $t('sitedata_sitetitle') ?>:</label>
                    <?= Html::input(
                        'text',
                        'SiteCreateForm[title]',
                        $model->title,
                        ['id' => 'siteTitle', 'class' => 'form-control']
                    ); ?>
                </div>

                <div class="form-group">
                    <label class="name" for="siteOrganization"><?= $t('sitedata_organization') ?>:</label>
                    <?= Html::input(
                        'text',
                        'SiteCreateForm[organization]',
                        $model->organization,
                        ['id' => 'siteOrganization', 'class' => 'form-control']
                    ); ?>
                </div>

                <?php

                /** @var \app\models\settings\AntragsgruenApp $params */
                $params = \yii::$app->params;
                $input  = Html::input('text', 'SiteCreateForm[subdomain]', $model->subdomain, ['id' => 'siteSubdomain']);
                ?>
                <div class="form-group">
                    <label class="url" for="subdomain"><?= $t('sitedata_subdomain') ?>:</label>
                    <div class="fakeurl">
                        <?php
                        if (strpos($params->domainSubdomain, '<subdomain:[\w_-]+>') !== false) {
                            echo str_replace('&lt;subdomain:[\w_-]+&gt;', $input, Html::encode($params->domainSubdomain));
                        } else {
                            echo $input;
                        }
                        ?>
                    </div>
                    <div class="labelSubInfo"><?= $t('sitedata_subdomain_hint') ?></div>
                </div>

                <div class="contact">
                    <label>
                        <strong><?= $t('sitedata_contact') ?>:</strong>
                        <small><?= $t('sitedata_contact_hint') ?></small>
                        <?= Html::textarea('SiteCreateForm[contact]', $model->contact, ['rows' => 5, 'required', 'id' => 'siteContact']) ?>
                    </label>

                </div>
            </div>
        </div>
    </fieldset>
    <div class="navigation">
        <button class="btn btn-lg btn-prev"><span class="icon-chevron-left"></span> <?= $t('prev') ?></button>
        <button type="submit" class="btn btn-lg btn-next btn-primary"><span class="icon-chevron-right"></span> <?= $t('finish') ?>
        </button>
    </div>
</div>