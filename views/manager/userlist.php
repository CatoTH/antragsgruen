<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var User[] $users
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = Yii::t('admin', 'users_head');
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(Yii::t('admin', 'users_bc'));

?>
    <h1><?= Yii::t('admin', 'users_head') ?></h1>
<?php
echo Html::beginForm('', 'post', [
    'class'                    => 'content',
    'data-antragsgruen-widget' => 'backend/UserList',
])
?>
    <table class="table siteAccountListTable">
        <thead>
        <tr>
            <th><?= Yii::t('admin', 'users_name') ?></th>
            <th><?= Yii::t('admin', 'users_auth') ?></th>
            <th><?= Yii::t('admin', 'users_registered') ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($users as $user) {
            ?>
            <tr>
                <td><?= Html::encode($user->name) ?></td>
                <td><?= Html::encode($user->getAuthName()) ?></td>
                <td><?= Tools::formatMysqlDateTime($user->dateCreation) ?></td>
                <td>
                    <?php
                    if ($user->id !== User::getCurrentUser()->id) {
                        ?>
                        <button type="button" data-id="<?= $user->id ?>" class="link deleteUser"
                                data-name="<?= Html::encode($user->name . ' / ' . $user->getAuthName()) ?>"
                                title="<?= Yii::t('admin', 'siteacc_del_btn') ?>">
                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                            <span class="sr-only"><?= Yii::t('admin', 'siteacc_del_btn') ?></span>
                        </button>
                        <?php
                    }
                    ?>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>
<?php
echo Html::endForm();
