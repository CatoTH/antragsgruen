<?php

use app\models\db\ConsultationMotionType;
use app\models\policies\IPolicy;
use app\models\supportTypes\ISupportType;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var ConsultationMotionType $motionType
 */

$policies = [];
foreach (IPolicy::getPolicies() as $policy) {
    $policies[$policy::getPolicyID()] = $policy::getPolicyName();
}

?>
<h3><?= \Yii::t('admin', 'motion_type_perm') ?></h3>

<!-- Policy for creating motions -->

<div class="form-group">
    <label class="col-md-4 control-label" for="typePolicyMotions">
        <?= \Yii::t('admin', 'motion_type_perm_motion') ?>
    </label>
    <div class="col-md-8">
        <?php
        echo Html::dropDownList(
            'type[policyMotions]',
            $motionType->policyMotions,
            $policies,
            ['id' => 'typePolicyMotions', 'class' => 'form-control']
        ) ?>
    </div>
</div>

<!--Support policy for motions -->


<div class="form-group">
    <label class="col-md-4 control-label" for="typePolicySupportMotions">
        <?= \Yii::t('admin', 'motion_type_perm_supp_mot') ?>
    </label>
    <div class="col-md-8">
        <?php
        echo Html::dropDownList(
            'type[policySupportMotions]',
            $motionType->policySupportMotions,
            $policies,
            ['id' => 'typePolicySupportMotions', 'class' => 'form-control']
        ) ?>
    </div>
</div>


<!--Support types for motions (Likes, Dislikes, Official support) -->

<div class="form-group">
    <div class="col-md-8 col-md-offset-4 contactDetails motionSupportPolicy">
        <div class="form-control">
            <?php
            $checkboxes = [
                [ISupportType::LIKEDISLIKE_LIKE, \Yii::t('admin', 'motion_type_like_like'), 'motionLike'],
                [ISupportType::LIKEDISLIKE_DISLIKE, \Yii::t('admin', 'motion_type_like_dislike'), 'motionDislike'],
                [ISupportType::LIKEDISLIKE_SUPPORT, \Yii::t('admin', 'motion_type_like_support'), 'motionSupport'],
            ];
            foreach ($checkboxes as $checkbox) {
                echo '<label>';
                echo Html::checkbox(
                    'type[motionLikesDislikes][]',
                    ($motionType->motionLikesDislikes & $checkbox[0]),
                    ['value' => $checkbox[0], 'class' => $checkbox[2]]
                );
                echo $checkbox[1] . '</label>';
            }
            ?>
        </div>
    </div>
</div>


<!-- Policy for creating amendments -->

<div class="form-group">
    <label class="col-md-4 control-label" for="typePolicyAmendments">
        <?= \Yii::t('admin', 'motion_type_perm_amend') ?>
    </label>
    <div class="col-md-8">
        <?php
        echo Html::dropDownList(
            'type[policyAmendments]',
            $motionType->policyAmendments,
            $policies,
            ['id' => 'typePolicyAmendments', 'class' => 'form-control']
        ) ?>
    </div>
</div>
<div class="form-group checkbox" id="typeAmendSinglePara">
    <div class="checkbox col-md-8 col-md-offset-4"><label>
            <?=Html::checkbox('type[amendSinglePara]', !$motionType->amendmentMultipleParagraphs)?>
            <?= \Yii::t('admin', 'motion_type_amend_singlep') ?></label></div>
</div>


<!-- Support policy for amendments -->

<div class="form-group">
    <label class="col-md-4 control-label" for="typePolicySupportAmendments">
        <?= \Yii::t('admin', 'motion_type_perm_supp_amend') ?>
    </label>
    <div class="col-md-8">
        <?php
        echo Html::dropDownList(
            'type[policySupportAmendments]',
            $motionType->policySupportAmendments,
            $policies,
            ['id' => 'typePolicySupportAmendments', 'class' => 'form-control']
        ) ?>
    </div>
</div>


<!-- Support types for amendments (Likes, Dislikes, Official support) -->

<div class="form-group">
    <div class="col-md-8 col-md-offset-4 contactDetails amendmentSupportPolicy">
        <div class="form-control">
            <?php
            $checkboxes = [
                [ISupportType::LIKEDISLIKE_LIKE, \Yii::t('admin', 'motion_type_like_like'), 'amendmentLike'],
                [ISupportType::LIKEDISLIKE_DISLIKE, \Yii::t('admin', 'motion_type_like_dislike'), 'amendmentDislike'],
                [ISupportType::LIKEDISLIKE_SUPPORT, \Yii::t('admin', 'motion_type_like_support'), 'amendmentSupport'],
            ];
            foreach ($checkboxes as $checkbox) {
                echo '<label>';
                echo Html::checkbox(
                    'type[amendmentLikesDislikes][]',
                    ($motionType->amendmentLikesDislikes & $checkbox[0]),
                    ['value' => $checkbox[0], 'class' => $checkbox[2]]
                );
                echo $checkbox[1] . '</label>';
            }
            ?>
        </div>
    </div>
</div>


<!--Policy for creating comments -->

<div class="form-group">
    <label class="col-md-4 control-label" for="typePolicyComments">
        <?= \Yii::t('admin', 'motion_type_perm_comment') ?>
    </label>
    <div class="col-md-8">
        <?php
        echo Html::dropDownList(
            'type[policyComments]',
            $motionType->policyComments,
            $policies,
            ['id' => 'typePolicyComments', 'class' => 'form-control']
        ); ?>
    </div>
</div>

<!-- Are initiators allowed to merge amendments into their motions -->

<div class="form-group initiatorsCanMergeRow">
    <div class="col-md-4 control-label">
        <?= \Yii::t('admin', 'motion_type_initiators_merge') ?>
    </div>
    <div class="col-md-8">
        <label><?php
            echo Html::radio(
                'type[initiatorsCanMergeAmendments]',
                ($motionType->initiatorsCanMergeAmendments == ConsultationMotionType::INITIATORS_MERGE_NEVER),
                [
                    'value' => ConsultationMotionType::INITIATORS_MERGE_NEVER,
                    'id'    => 'initiatorsCanMerge' . ConsultationMotionType::INITIATORS_MERGE_NEVER,
                ]
            );
            ?> <?= \Yii::t('admin', 'motion_type_initiators_merge_no') ?>
        </label>
        <label><?php
            echo Html::radio(
                'type[initiatorsCanMergeAmendments]',
                ($motionType->initiatorsCanMergeAmendments == ConsultationMotionType::INITIATORS_MERGE_NO_COLLISSION),
                [
                    'value' => ConsultationMotionType::INITIATORS_MERGE_NO_COLLISSION,
                    'id'    => 'initiatorsCanMerge' . ConsultationMotionType::INITIATORS_MERGE_NO_COLLISSION,
                ]
            );
            ?> <?= \Yii::t('admin', 'motion_type_initiators_merge_nocoll') ?>
            <span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="top"
                  title="<?=addslashes(\Yii::t('admin', 'motion_type_initiators_merge_nocoll_hint'))?>"></span>
        </label>
        <label><?php
            echo Html::radio(
                'type[initiatorsCanMergeAmendments]',
                ($motionType->initiatorsCanMergeAmendments == ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISSION),
                [
                    'value' => ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISSION,
                    'id'    => 'initiatorsCanMerge' . ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISSION,
                ]
            )
            ?> <?= \Yii::t('admin', 'motion_type_initiators_merge_yes') ?>
            <span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="top"
                  title="<?=addslashes(\Yii::t('admin', 'motion_type_initiators_merge_yes_hint'))?>"></span>
        </label>
    </div>
</div>

<?php
?>