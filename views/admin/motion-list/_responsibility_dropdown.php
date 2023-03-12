<?php

use app\components\UrlHelper;
use app\models\settings\PrivilegeQueryContext;
use app\models\settings\Privileges;
use app\models\db\{IMotion, User};
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var IMotion $imotion
 * @var string $type
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;

$idBase = $type . $imotion->id;

$users = [];
$foundUsers = [];
foreach ($controller->consultation->getUsersInAnyGroup() as $user) {
    $pctx = PrivilegeQueryContext::imotion($imotion);
    if ($user->hasPrivilege($controller->consultation, Privileges::PRIVILEGE_ANY, $pctx)) {
        $users[]      = $user;
        $foundUsers[] = $user->id;
    }
}
usort($users, function (User $user1, User $user2): int {
    return strnatcasecmp($user1->name ?? '', $user2->name ?? '');
});

$saveUrl = UrlHelper::createUrl([
    'admin/proposed-procedure/save-responsibility',
    'type' => $type,
    'id'   => $imotion->id,
]);

$responsibilityUser = ($imotion->responsibilityId !== null ? $imotion->responsibilityUser : null);
?>
<div class="dropdown respHolder" data-save-url="<?= Html::encode($saveUrl) ?>">
    <button class="respButton dropdown-toggle btn-link" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" id="resp<?= $idBase ?>">
        <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
        <span class="sr-only"><?= Yii::t('admin', 'list_responsible_edit') ?></span>
    </button>
    <span class="responsibilityUser" data-id="<?= $imotion->responsibilityId ?>"><?php
        if ($responsibilityUser) {
            echo Html::encode($responsibilityUser->name ?? $responsibilityUser->getAuthName());
        }
        ?></span>
    <span class="responsibilityComment"><?= Html::encode($imotion->responsibilityComment) ?></span>
    <ul class="dropdown-menu" aria-labelledby="resp<?= $idBase ?>">
        <li class="respUser respUserNone <?= (!$responsibilityUser ? 'selected' : '') ?>" data-user-id="0">
            <a href="" class="setResponsibility"><?= Yii::t('admin', 'list_responsible_none') ?></a>
        </li>
        <?php
        foreach ($users as $user) {
            if ($responsibilityUser && $responsibilityUser->id === $user->id) {
                echo '<li class="respUser respUser' . $user->id . ' selected" data-user-id="' . $user->id . '">';
            } else {
                echo '<li class="respUser respUser' . $user->id . '" data-user-id="' . $user->id . '">';
            }
            echo '<a href="" class="setResponsibility">';
            echo '<span class="name">' . Html::encode($user->name) . '</span> <small>(' . Html::encode($user->getAuthName()) . ')</small>';
            echo '</a></li>';
        }
        ?>
        <li class="respCommentRow">
            <label for="respComm<?= $idBase ?>" hidden><?= Yii::t('admin', 'list_responsible_comment') ?></label>
            <div class="input-group">
                <input class="form-control" type="text" id="respComm<?= $idBase ?>"
                       value="<?= Html::encode($imotion->responsibilityComment ?: '') ?>"
                       placeholder="<?= Yii::t('admin', 'list_responsible_comment') ?>"><span
                    class="input-group-btn"><button class="btn btn-default" type="button"><span class="glyphicon glyphicon-edit"></span></button></span>
            </div>
        </li>
    </ul>
</div>
