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

$this->title = \Yii::t('admin', 'users_head');
$layout->addCSS('css/backend.css');
$layout->addBreadcrumb(\Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb(\Yii::t('admin', 'users_bc'));

?>
<h1><?=\Yii::t('admin', 'index_settings')?></h1>
<div class="content">
    <table class="table">
        <thead>
        <tr>
            <th><?=\Yii::t('admin', 'users_name')?></th>
            <th><?=\Yii::t('admin', 'users_auth')?></th>
            <th><?=\Yii::t('admin', 'users_registered')?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($users as $user) {
            echo '<tr>';
            echo '<td>' . Html::encode($user->name) . '</td>';
            echo '<td>' . Html::encode($user->getAuthName()) . '</td>';
            echo '<td>' . Tools::formatMysqlDateTime($user->dateCreation) . '</td>';
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
</div>
