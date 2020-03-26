<?php

use app\components\HTMLTools;
use app\models\db\ConsultationMotionType;
use app\models\policies\IPolicy;
use app\models\supportTypes\SupportBase;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var ConsultationMotionType $motionType
 */

$policies = [];
foreach (IPolicy::getPolicies() as $policy) {
    $policies[$policy::getPolicyID()] = $policy::getPolicyName();
}

?>
<h2 class="h3"><?= Yii::t('admin', 'motion_type_perm') ?></h2>

<!-- Policy for creating motions -->

<div class="form-group">
    <label class="col-md-4 control-label" for="typePolicyMotions">
        <?= Yii::t('admin', 'motion_type_perm_motion') ?>
    </label>
    <div class="col-md-8">
        <?php
        echo HTMLTools::fueluxSelectbox(
            'type[policyMotions]',
            $policies,
            $motionType->policyMotions,
            ['id' => 'typePolicyMotions'],
            true
        );
        ?>
    </div>
</div>

<!--Support policy for motions -->


<div class="form-group">
    <label class="col-md-4 control-label" for="typePolicySupportMotions">
        <?= Yii::t('admin', 'motion_type_perm_supp_mot') ?>
    </label>
    <div class="col-md-8">
        <?php
        echo HTMLTools::fueluxSelectbox(
            'type[policySupportMotions]',
            $policies,
            $motionType->policySupportMotions,
            ['id' => 'typePolicySupportMotions'],
            true
        );
        ?>
    </div>
</div>


<!--Support types for motions (Likes, Dislikes, Official support) -->

<div class="form-group">
    <fieldset class="col-md-8 col-md-offset-4 contactDetails motionSupportPolicy">
        <legend class="sr-only"><?= Yii::t('admin', 'motion_type_like_title') ?></legend>
        <div class="form-control">
            <?php
            $checkboxes = [
                [SupportBase::LIKEDISLIKE_LIKE, Yii::t('admin', 'motion_type_like_like'), 'motionLike'],
                [SupportBase::LIKEDISLIKE_DISLIKE, Yii::t('admin', 'motion_type_like_dislike'), 'motionDislike'],
                [SupportBase::LIKEDISLIKE_SUPPORT, Yii::t('admin', 'motion_type_like_support'), 'motionSupport'],
            ];
            foreach ($checkboxes as $checkbox) {
                echo '<label>';
                echo Html::checkbox(
                    'type[motionLikesDislikes][]',
                    ($motionType->motionLikesDislikes & $checkbox[0]),
                    ['value' => $checkbox[0], 'class' => $checkbox[2]]
                );
                echo $checkbox[1];

                if ($checkbox[0] === SupportBase::LIKEDISLIKE_LIKE) {
                    echo HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_type_like_like_h'));
                }
                if ($checkbox[0] === SupportBase::LIKEDISLIKE_SUPPORT) {
                    echo HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_type_like_support_h'));
                }

                echo '</label>';
            }
            ?>
        </div>
    </fieldset>
</div>


<!-- Policy for creating amendments -->

<div class="form-group">
    <label class="col-md-4 control-label" for="typePolicyAmendments">
        <?= Yii::t('admin', 'motion_type_perm_amend') ?>
    </label>
    <div class="col-md-8">
        <?php
        echo HTMLTools::fueluxSelectbox(
            'type[policyAmendments]',
            $policies,
            $motionType->policyAmendments,
            ['id' => 'typePolicyAmendments'],
            true
        ) ?>
    </div>
</div>
<div class="form-group checkboxNoPadding">
    <div class="col-md-8 col-md-offset-4">
        <?php
        echo HTMLTools::labeledCheckbox(
            'type[amendSinglePara]',
            Yii::t('admin', 'motion_type_amend_singlep'),
            !$motionType->amendmentMultipleParagraphs,
            'typeAmendSinglePara'
        );
        ?>
    </div>
</div>


<!-- Support policy for amendments -->

<div class="form-group">
    <label class="col-md-4 control-label" for="typePolicySupportAmendments">
        <?= Yii::t('admin', 'motion_type_perm_supp_amend') ?>
    </label>
    <div class="col-md-8">
        <?php
        echo HTMLTools::fueluxSelectbox(
            'type[policySupportAmendments]',
            $policies,
            $motionType->policySupportAmendments,
            ['id' => 'typePolicySupportAmendments'],
            true
        );
        ?>
    </div>
</div>


<!-- Support types for amendments (Likes, Dislikes, Official support) -->

<div class="form-group">
    <fieldset class="col-md-8 col-md-offset-4 contactDetails amendmentSupportPolicy">
        <legend class="sr-only"><?= Yii::t('admin', 'motion_type_like_title') ?></legend>
        <div class="form-control">
            <?php
            $checkboxes = [
                [SupportBase::LIKEDISLIKE_LIKE, Yii::t('admin', 'motion_type_like_like'), 'amendmentLike'],
                [SupportBase::LIKEDISLIKE_DISLIKE, Yii::t('admin', 'motion_type_like_dislike'), 'amendmentDislike'],
                [SupportBase::LIKEDISLIKE_SUPPORT, Yii::t('admin', 'motion_type_like_support'), 'amendmentSupport'],
            ];
            foreach ($checkboxes as $checkbox) {
                echo '<label>';
                echo Html::checkbox(
                    'type[amendmentLikesDislikes][]',
                    ($motionType->amendmentLikesDislikes & $checkbox[0]),
                    ['value' => $checkbox[0], 'class' => $checkbox[2]]
                );

                echo $checkbox[1];

                if ($checkbox[0] === SupportBase::LIKEDISLIKE_LIKE) {
                    echo HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_type_like_like_h'));
                }
                if ($checkbox[0] === SupportBase::LIKEDISLIKE_SUPPORT) {
                    echo HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_type_like_support_h'));
                }

                echo '</label>';
            }
            ?>
        </div>
    </fieldset>
</div>


<!--Policy for creating comments -->

<div class="form-group">
    <label class="col-md-4 control-label" for="typePolicyComments">
        <?= Yii::t('admin', 'motion_type_perm_comment') ?>
    </label>
    <div class="col-md-8">
        <?php
        echo HTMLTools::fueluxSelectbox(
            'type[policyComments]',
            $policies,
            $motionType->policyComments,
            ['id' => 'typePolicyComments'],
            true
        ); ?>
    </div>
</div>

<!-- Are initiators allowed to merge amendments into their motions -->

<fieldset class="form-group initiatorsCanMergeRow">
    <legend class="col-md-4 control-label">
        <?= Yii::t('admin', 'motion_type_initiators_merge') ?>
    </legend>
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
            ?> <?= Yii::t('admin', 'motion_type_initiators_merge_no') ?>
        </label>
        <label><?php
            echo Html::radio(
                'type[initiatorsCanMergeAmendments]',
                ($motionType->initiatorsCanMergeAmendments == ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION),
                [
                    'value' => ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION,
                    'id'    => 'initiatorsCanMerge' . ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION,
                ]
            );
            ?> <?= Yii::t('admin', 'motion_type_initiators_merge_nocoll') ?>
            <?= HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_type_initiators_merge_nocoll_hint')) ?>
        </label>
        <label><?php
            echo Html::radio(
                'type[initiatorsCanMergeAmendments]',
                ($motionType->initiatorsCanMergeAmendments == ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISION),
                [
                    'value' => ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISION,
                    'id'    => 'initiatorsCanMerge' . ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISION,
                ]
            )
            ?> <?= Yii::t('admin', 'motion_type_initiators_merge_yes') ?>
            <?= HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_type_initiators_merge_yes_hint')) ?>
        </label>
    </div>
</fieldset>

<?php
?>
