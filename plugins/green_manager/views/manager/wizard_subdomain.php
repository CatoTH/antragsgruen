<?php

use app\models\db\User;
use yii\helpers\Html;

/**
 * @var \app\models\forms\SiteCreateForm $model
 * @var Callable $t
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

                <div class="form-group has-feedback">
                    <label class="name" for="siteOrganization"><?= $t('sitedata_organization') ?>:</label>
                    <?= Html::input(
                        'text',
                        'SiteCreateForm[organization]',
                        $model->organization,
                        ['id' => 'siteOrganization', 'class' => 'form-control', 'required' => 'required']
                    ); ?>
                    <span class="error glyphicon glyphicon-remove form-control-feedback" aria-hidden="true"></span>
                    <span class="success glyphicon glyphicon-ok form-control-feedback" aria-hidden="true"></span>
                </div>

                <?php

                /** @var \app\models\settings\AntragsgruenApp $params */
                $params     = Yii::$app->params;
                $requestUrl = \app\components\UrlHelper::createUrl(['manager/check-subdomain', 'test' => 'SUBDOMAIN']);
                $input      = '<div class="form-group has-feedback">';
                $opts       = ['id' => 'siteSubdomain', 'class' => 'form-control', 'data-query-url' => $requestUrl, 'required' => 'required'];
                $input      .= Html::input('text', 'SiteCreateForm[subdomain]', $model->subdomain, $opts);
                $input      .= '<span class="error glyphicon glyphicon-remove form-control-feedback" aria-hidden="true"></span>';
                $input      .= '<span class="success glyphicon glyphicon-ok form-control-feedback" aria-hidden="true"></span>';
                $input      .= '</div>';
                ?>
                <div class="subdomainRow">
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
                    <div class="subdomainError hidden"
                         data-template="<?= Html::encode($t('sitedata_subdomain_err')) ?>"><?= $t('sitedata_subdomain_err') ?></div>
                </div>

                <?php
                if (!User::getCurrentUser()) {
                    ?>
                    <br>
                    <legend>Create an account to manage the site</legend>

                    <div class="usernameRow">
                        <label for="userName">Your name:</label>
                        <?= Html::input(
                            'text',
                            'SiteCreateForm[user_name]',
                            '',
                            ['id' => 'userName', 'class' => 'form-control', 'required' => 'required']
                        ); ?>
                    </div>
                    <div class="usernameRow">
                        <label for="userEmail">Your e-mail-address / username:</label>
                        <?= Html::input(
                            'text',
                            'SiteCreateForm[user_email]',
                            '',
                            ['id' => 'userEmail', 'class' => 'form-control', 'required' => 'required']
                        ); ?>
                    </div>
                    <div class="passwordRow">
                        <label for="userPass">Password for your account:</label>
                        <?= Html::input(
                            'password',
                            'SiteCreateForm[user_pwd]',
                            '',
                            ['id' => 'userPass', 'class' => 'form-control', 'required' => 'required']
                        ); ?>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </fieldset>
    <div class="navigation">
        <button class="btn btn-lg btn-prev"><span class="icon-chevron-left"></span> <?= $t('prev') ?></button>
        <button type="submit" class="btn btn-lg btn-next btn-primary" name="create">
            <span class="icon-chevron-right" aria-hidden="true"></span> <?= $t('finish') ?>
        </button>
    </div>
</div>
