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

<div class="adminTwoCols hideForAmendmentsOnly">
    <label class="leftColumn" for="typePolicyMotions">
        <?= Yii::t('admin', 'motion_type_perm_motion') ?>:
    </label>
    <div class="rightColumn">
        <?php
        echo Html::dropDownList(
            'type[policyMotions]',
            $motionType->policyMotions,
            $policies,
            ['id' => 'typePolicyMotions', 'class' => 'stdDropdown']
        );
        ?>
    </div>
</div>

<!--Support policy for motions -->


<div class="adminTwoCols hideForAmendmentsOnly">
    <label class="leftColumn" for="typePolicySupportMotions">
        <?= Yii::t('admin', 'motion_type_perm_supp_mot') ?>:
    </label>
    <div class="rightColumn">
        <?php
        echo Html::dropDownList(
            'type[policySupportMotions]',
            $motionType->policySupportMotions,
            $policies,
            ['id' => 'typePolicySupportMotions', 'class' => 'stdDropdown']
        );
        ?>
    </div>
</div>


<!--Support types for motions (Likes, Dislikes, Official support) -->

<div class="adminTwoCols hideForAmendmentsOnly">
    <div class="leftColumn"></div>
    <div class="rightColumn">
        <fieldset class="contactDetails motionSupportPolicy">
            <legend class="sr-only"><?= Yii::t('admin', 'motion_type_like_title') ?></legend>
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
        </fieldset>
    </div>
</div>


<!-- Policy for creating amendments -->

<div class="adminTwoCols">
    <label class="leftColumn" for="typePolicyAmendments">
        <?= Yii::t('admin', 'motion_type_perm_amend') ?>:
    </label>
    <div class="rightColumn">
        <?php
        echo Html::dropDownList(
            'type[policyAmendments]',
            $motionType->policyAmendments,
            $policies,
            ['id' => 'typePolicyAmendments', 'class' => 'stdDropdown']
        ) ?>
    </div>
</div>
<div class="adminTwoCols checkboxNoPadding">
    <div class="leftColumn"></div>
    <div class="rightColumn">
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
<div class="adminTwoCols checkboxNoPadding">
    <div class="leftColumn"></div>
    <div class="rightColumn">
        <?php
        echo HTMLTools::labeledCheckbox(
            'type[allowAmendmentsToAmendments]',
            Yii::t('admin', 'motion_type_allow_amend_amend'),
            $motionType->getSettingsObj()->allowAmendmentsToAmendments,
            'allowAmendmentsToAmendments'
        );
        ?>
    </div>
</div>


<!-- Support policy for amendments -->

<div class="adminTwoCols">
    <label class="leftColumn" for="typePolicySupportAmendments">
        <?= Yii::t('admin', 'motion_type_perm_supp_amend') ?>:
    </label>
    <div class="rightColumn">
        <?php
        echo Html::dropDownList(
            'type[policySupportAmendments]',
            $motionType->policySupportAmendments,
            $policies,
            ['id' => 'typePolicySupportAmendments', 'class' => 'stdDropdown']
        );
        ?>
    </div>
</div>


<!-- Support types for amendments (Likes, Dislikes, Official support) -->

<div class="adminTwoCols">
    <div class="leftColumn"></div>
    <div class="rightColumn">
        <fieldset class="contactDetails amendmentSupportPolicy">
            <legend class="sr-only"><?= Yii::t('admin', 'motion_type_like_title') ?></legend>
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
        </fieldset>
    </div>
</div>


<!--Policy for creating comments -->

<div class="adminTwoCols">
    <label class="leftColumn" for="typePolicyComments">
        <?= Yii::t('admin', 'motion_type_perm_comment') ?>:
    </label>
    <div class="rightColumn">
        <?php
        echo Html::dropDownList(
            'type[policyComments]',
            $motionType->policyComments,
            $policies,
            ['id' => 'typePolicyComments', 'class' => 'stdDropdown']
        ); ?>
    </div>
</div>

<div class="adminTwoCols checkboxNoPadding">
    <div class="leftColumn"></div>
    <div class="rightColumn">
        <?php
        echo HTMLTools::labeledCheckbox(
            'type[commentsRestrictViewToWritables]',
            Yii::t('admin', 'motion_type_perm_comment_restrict'),
            $motionType->getSettingsObj()->commentsRestrictViewToWritables,
            'commentsRestrictViewToWritables'
        );
        ?>
    </div>
</div>


<!-- Are initiators allowed to merge amendments into their motions -->

<div class="adminTwoCols initiatorsCanMergeRow hideForAmendmentsOnly">
    <div class="leftColumn">
        <?= Yii::t('admin', 'motion_type_initiators_merge') ?>:
    </div>
    <div class="rightColumn">
        <fieldset>
            <legend class="hidden"><?= Yii::t('admin', 'motion_type_initiators_merge') ?></legend>
            <label><?php
                echo Html::radio(
                    'type[initiatorsCanMergeAmendments]',
                    ($motionType->initiatorsCanMergeAmendments == ConsultationMotionType::INITIATORS_MERGE_NEVER),
                    [
                        'value' => ConsultationMotionType::INITIATORS_MERGE_NEVER,
                        'id' => 'initiatorsCanMerge' . ConsultationMotionType::INITIATORS_MERGE_NEVER,
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
                        'id' => 'initiatorsCanMerge' . ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION,
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
                        'id' => 'initiatorsCanMerge' . ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISION,
                    ]
                )
                ?> <?= Yii::t('admin', 'motion_type_initiators_merge_yes') ?>
                <?= HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_type_initiators_merge_yes_hint')) ?>
            </label>
        </fieldset>
    </div>
</div>

<?php
?>
