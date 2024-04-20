<?php

use app\models\db\{ConsultationMotionType, ConsultationUserGroup};
use app\models\policies\IPolicy;
use yii\helpers\Html;

/**
 * @var string $id
 * @var string $formName
 * @var ConsultationMotionType $motionType
 * @var IPolicy $currentPolicy
 */

if (ConsultationUserGroup::consultationHasLoadableUserGroups($motionType->getConsultation())) {
    $groupLoadUrl = \app\components\UrlHelper::createUrl('/admin/users/search-groups');
} else {
    $groupLoadUrl = '';
}
if (is_a($currentPolicy, \app\models\policies\UserGroups::class)) {
    $preselectedUserGroupsIds = array_map(function (ConsultationUserGroup $group): int { return $group->id; }, $currentPolicy->getAllowedUserGroups());
} else {
    $preselectedUserGroupsIds = [];
}
?>
<div class="userGroupSelect" data-load-url="<?= Html::encode($groupLoadUrl) ?>">
    <select id="<?= $id ?>" name="type[<?= $formName ?>][groups][]" multiple
            placeholder="<?= Yii::t('admin', 'motion_type_group_ph') ?>" title="<?= Yii::t('admin', 'motion_type_group_title') ?>">
        <?php
        foreach ($motionType->getConsultation()->getAllAvailableUserGroups($preselectedUserGroupsIds) as $group) {
            echo '<option value="' . $group->id . '"';
            if (is_a($currentPolicy, \app\models\policies\UserGroups::class) && $currentPolicy->allowsUserGroup($group)) {
                echo ' selected';
            }
            echo '>';
            echo Html::encode($group->getNormalizedTitle());
            echo '</option>';
        }
        ?>
    </select>
</div>
