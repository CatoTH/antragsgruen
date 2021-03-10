<?php

use app\components\HTMLTools;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var array $admins
 * @var \yii\web\Controller $controller
 */
$hasProposedProcedures = $controller->consultation->hasProposedProcedures();

?>
<div class="adminForm form-horizontal">
    <h2 class="green"><?= Yii::t('admin', 'siteacc_admins_title') ?></h2>
    <section class="content">
        <?php
        echo Html::beginForm('', 'post', ['id' => 'adminForm', 'class' => 'adminForm form-horizontal siteaccAdminsForm']);

        $myself = User::getCurrentUser();

        foreach ($admins as $admin) {
            $types = $admin['types'];
            /** @var User $user */
            $user = $admin['user'];

            $isMe = ($user->id === User::getCurrentUser()->id);
            ?>
            <section class="adminCard admin<?= $user->id ?>">
                <header>
                    <?php
                    if (!$isMe) { ?>
                        <button class="link removeAdmin removeAdmin<?= $user->id ?>"
                                type="button" data-id="<?= $user->id ?>"
                                title="<?= Yii::t('admin', 'siteacc_admins_del') ?>">
                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                            <span class="sr-only"><?= Yii::t('admin', 'siteacc_admins_del') ?></span>
                        </button>
                        <?php
                    }

                    echo '<span class="name">';
                    echo Html::encode($user->getAuthName());
                    if ($user->name !== '') {
                        echo ' (' . Html::encode($user->name) . ')';
                    }
                    echo '</span>';
                    ?>
                </header>
                <main>
                    <label class="type typeSite">
                        <input type="checkbox" name="adminTypes[<?= $user->id ?>][]" value="site"
                            <?= (in_array('site', $types) ? 'checked' : '') ?>
                            <?= ($isMe ? 'disabled' : '') ?>>
                        <?= Yii::t('admin', 'siteacc_admins_all_cons') ?>
                    </label>
                    <label class="type typeCon">
                        <input type="checkbox" name="adminTypes[<?= $user->id ?>][]" value="consultation"
                            <?= (in_array('consultation', $types) ? 'checked' : '') ?>
                            <?= ($isMe ? 'disabled' : '') ?>>
                        <?= Yii::t('admin', 'siteacc_admins_one_con') ?>
                    </label>
                    <label class="type typeProposal">
                        <input type="checkbox" name="adminTypes[<?= $user->id ?>][]" value="proposal"
                            <?= (in_array('proposal', $types) ? 'checked' : '') ?>
                            <?= ($isMe ? 'disabled' : '') ?>>
                        <?= Yii::t('admin', 'siteacc_admins_proposals') ?>
                    </label>
                    <?php
                    if ($hasProposedProcedures) {
                        if ($user->getSettingsObj()->ppReplyTo === '') {
                            ?>
                            <button class="btn btn-link pull-right ppReplyToOpener" type="button"
                                    title="<?= Html::encode(Yii::t('admin', 'siteacc_admins_pp_replyto')) ?>">
                                @
                            </button>
                            <?php
                        }
                        ?>
                        <div class="ppReplyTo row <?= ($user->getSettingsObj()->ppReplyTo === '' ? 'hidden' : '') ?>">
                            <label class="col-md-6 control-label" for="ppReplyTo<?= $user->id ?>">
                                <?= Yii::t('admin', 'siteacc_admins_pp_replyto') ?>:
                            </label>
                            <div class="col-md-5">
                                <input class="form-control" id="ppReplyTo<?= $user->id ?>" name="ppReplyTo[<?= $user->id ?>]"
                                       value="<?= Html::encode($user->getSettingsObj()->ppReplyTo) ?>">
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </main>
            </section>
            <?php
        }
        ?>
        <div class="saveRow">
            <button type="submit" name="saveAdmin" class="btn btn-primary"><?= Yii::t('base', 'save') ?></button>
        </div>
        <?php
        echo Html::endForm();
        ?>

        <br>

        <?php
        echo Html::beginForm('', 'post', ['id' => 'adminAddForm', 'class' => 'adminForm form-horizontal']);

        $loginTypes = [
            'email' => Yii::t('admin', 'siteacc_add_email') . ':',
        ];
        if ($controller->getParams()->isSamlActive()) {
            $loginTypes['gruenesnetz'] = Yii::t('admin', 'siteacc_add_ww') . ':';
        }
        ?>
        <h4><?= Yii::t('admin', 'siteacc_admins_add') ?></h4>
        <div class="row">
            <div class="col-md-3 admin-type">
                <?= HTMLTools::fueluxSelectbox('addType', $loginTypes) ?>
            </div>
            <div class="col-md-4">
                <input type="text" name="addUsername" value="" id="addUsername" class="form-control"
                       title="<?= Html::encode(Yii::t('admin', 'siteacc_add_name_title')) ?>"
                       placeholder="<?= Html::encode(Yii::t('admin', 'siteacc_add_name_place')) ?>" required>
            </div>
            <div class="col-md-3">
                <button type="submit" name="addAdmin"
                        class="btn btn-primary"><?= Yii::t('admin', 'siteacc_add_btn') ?></button>
            </div>
        </div>
        <?php
        echo Html::endForm();
        ?>
        <br><br>
    </section>
</div>
