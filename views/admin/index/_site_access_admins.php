<?php

use app\components\HTMLTools;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var array $admins
 */

?>
    <div class="adminForm form-horizontal">
        <h2 class="green"><?= \Yii::t('admin', 'siteacc_admins_title') ?></h2>
        <section class="content">
            <?php
            echo Html::beginForm('', 'post', ['id' => 'adminForm', 'class' => 'adminForm form-horizontal']);
            ?>
            <table class="table">
                <thead>
                <tr>
                    <th><?= \Yii::t('admin', 'siteacc_admins_user') ?></th>
                    <th class="type type-con"><?= \Yii::t('admin', 'siteacc_admins_one_con') ?></th>
                    <th class="type type-site"><?= \Yii::t('admin', 'siteacc_admins_all_cons') ?></th>
                    <th class="del"><?= \Yii::t('admin', 'siteacc_admins_del') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $myself = User::getCurrentUser();

                foreach ($admins as $admin) {
                    $type = $admin['type'];
                    /** @var User $user */
                    $user = $admin['user'];

                    $isMe = ($user->id == User::getCurrentUser()->id);
                    ?>
                    <tr class="admin<?= $user->id ?>">
                        <td>
                            <?php
                            echo Html::encode($user->getAuthName());
                            if ($user->name != '') {
                                echo ' (' . Html::encode($user->name) . ')';
                            }
                            ?>
                        </td>
                        <td class="type type-con">
                            <input type="radio" name="adminType[<?= $user->id ?>]" value="consultation"
                                <?= ($type == 'consultation' ? 'checked' : '') ?>
                                <?= ($isMe ? 'disabled' : '') ?>
                                   title="<?= Html::encode(\Yii::t('admin', 'siteacc_admins_one_con')) ?>">
                        </td>
                        <td class="type type-site">
                            <input type="radio" name="adminType[<?= $user->id ?>]" value="site"
                                <?= ($type == 'site' ? 'checked' : '') ?>
                                <?= ($isMe ? 'disabled' : '') ?>
                                   title="<?= Html::encode(\Yii::t('admin', 'siteacc_admins_all_cons')) ?>">
                        </td>
                        <td class="del">
                            <?php
                            if (!$isMe) { ?>
                                <button class="link removeAdmin removeAdmin<?= $user->id ?>"
                                        type="button" data-id="<?= $user->id ?>">
                                    <span class="glyphicon glyphicon-trash"></span>
                                </button>
                                <?php
                            } ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <div class="save-row">
                <button type="submit" name="saveAdmin" class="btn btn-primary"><?= \Yii::t('base', 'save') ?></button>
            </div>
            <?php
            echo Html::endForm();
            ?>

            <br>

            <?php
            echo Html::beginForm('', 'post', ['id' => 'adminAddForm', 'class' => 'adminForm form-horizontal']);
            ?>
            <h4><?= \Yii::t('admin', 'siteacc_admins_add') ?></h4>
            <div class="row">
                <div class="col-md-3 admin-type">
                    <?= HTMLTools::fueluxSelectbox('addType', [
                        'wurzelwerk' => \Yii::t('admin', 'siteacc_add_ww') . ':',
                        'email'      => \Yii::t('admin', 'siteacc_add_email') . ':',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <input type="text" name="addUsername" value="" id="addUsername" class="form-control"
                           title="<?= Html::encode(\Yii::t('admin', 'siteacc_add_name_title')) ?>"
                           placeholder="<?= Html::encode(\Yii::t('admin', 'siteacc_add_name_place')) ?>" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="addAdmin"
                            class="btn btn-primary"><?= \Yii::t('admin', 'siteacc_add_btn') ?></button>
                </div>
            </div>
            <?php
            echo Html::endForm();
            ?>
            <br><br>
        </section>
    </div>
<?

