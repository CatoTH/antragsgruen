<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Motion $motion
 */

use app\components\HTMLTools;
use app\components\Tools;
use app\models\db\Motion;
use yii\helpers\Html;

$saveUrl = \app\components\UrlHelper::createMotionUrl($motion, 'save-proposal-status');
echo Html::beginForm($saveUrl, 'POST', [
    'id'                       => 'proposedChanges',
    'data-antragsgruen-widget' => 'backend/ChangeProposedProcedure',
    'class'                    => 'fuelux',
]);
if ($motion->proposalStatus == Motion::STATUS_REFERRED) {
    $preReferredTo = $motion->proposalComment;
} else {
    $preReferredTo = '';
}
if ($motion->proposalStatus == Motion::STATUS_OBSOLETED_BY) {
    $preObsoletedBy = $motion->proposalComment;
} else {
    $preObsoletedBy = '';
}
if ($motion->proposalStatus == Motion::STATUS_CUSTOM_STRING) {
    $preCustomStr = $motion->proposalComment;
} else {
    $preCustomStr = '';
}

if (isset($msgAlert) && $msgAlert !== null) {
    echo '<div class="alert alert-info">' . $msgAlert . '</div>';
}

$votingBlocks = $motion->getMyConsultation()->votingBlocks;
?>
    <h2>
        <?= \Yii::t('amend', 'proposal_amend_title') ?>
        <button class="pull-right btn-link closeBtn" type="button"
                title="<?= Html::encode(\Yii::t('amend', 'proposal_close')) ?>">
            <span class="glyphicon glyphicon-chevron-up"></span>
        </button>
    </h2>
    <div class="holder">
        <section class="statusForm">
            <h3><?= \Yii::t('amend', 'proposal_status_title') ?></h3>

            <?php
            $foundStatus = false;
            foreach (Motion::getProposedChangeStati() as $statusId) {
                ?>
                <label class="proposalStatus<?= $statusId ?>">
                    <input type="radio" name="proposalStatus" value="<?= $statusId ?>" <?php
                    if ($motion->proposalStatus == $statusId) {
                        $foundStatus = true;
                        echo 'checked';
                    }
                    ?>> <?= Motion::getProposedStatiNames()[$statusId] ?>
                </label><br>
                <?php
            }
            ?>
            <label>
                <?= Html::radio('proposalStatus', !$foundStatus, ['value' => '0']) ?>
                - <?= \Yii::t('amend', 'proposal_status_na') ?> -
            </label>
        </section>
        <div class="middleCol">
            <div class="visibilitySettings showIfStatusSet">
                <h3><?= \Yii::t('amend', 'proposal_publicity') ?></h3>
                <label>
                    <?= Html::checkbox('proposalVisible', ($motion->proposalVisibleFrom !== null)) ?>
                    <?= \Yii::t('amend', 'proposal_visible') ?>
                </label>
                <label>
                    <?= Html::checkbox('setPublicExplanation', ($motion->proposalExplanation !== null)) ?>
                    <?= \Yii::t('amend', 'proposal_public_expl_set') ?>
                </label>
            </div>
            <div class="votingBlockSettings showIfStatusSet">
                <h3><?= \Yii::t('amend', 'proposal_voteblock') ?></h3>
                <?php
                $options = ['-'];
                foreach ($votingBlocks as $votingBlock) {
                    $options[$votingBlock->id] = $votingBlock->title;
                }
                $options['NEW'] = '- ' . \Yii::t('amend', 'proposal_voteblock_newopt') . ' -';
                $attrs          = ['id' => 'votingBlockId', 'class' => 'form-control'];
                echo HTMLTools::fueluxSelectbox('votingBlockId', $options, $motion->votingBlockId, $attrs);
                ?>
                <div class="newBlock">
                    <label for="newBlockTitle" class="control-label">
                        <?= \Yii::t('amend', 'proposal_voteblock_new') ?>:
                    </label>
                    <input type="text" class="form-control" id="newBlockTitle" name="newBlockTitle">
                </div>
            </div>
            <div class="notificationSettings showIfStatusSet">
                <h3><?= \Yii::t('amend', 'proposal_noti') ?></h3>
                <div class="notificationStatus">
                    <?php
                    if ($motion->proposalUserStatus !== null) {
                        if ($motion->proposalUserStatus == Motion::STATUS_ACCEPTED) {
                            echo '<span class="glyphicon glyphicon glyphicon-ok accepted"></span>';
                            echo \Yii::t('amend', 'proposal_user_accepted');
                        } elseif ($motion->proposalUserStatus == Motion::STATUS_REJECTED) {
                            echo '<span class="glyphicon glyphicon glyphicon-remove rejected"></span>';
                            echo \Yii::t('amend', 'proposal_user_rejected');
                        } else {
                            echo 'Error: unknown response of the proposer';
                        }
                    } elseif ($motion->proposalNotification !== null) {
                        $msg  = \Yii::t('amend', 'proposal_notified');
                        $date = Tools::formatMysqlDate($motion->proposalNotification, null, false);
                        echo str_replace('%DATE%', $date, $msg);
                        if ($motion->proposalStatusNeedsUserFeedback()) {
                            echo ' ' . \Yii::t('amend', 'proposal_no_feedback');
                        }
                    } elseif ($motion->proposalStatus !== null) {
                        if ($motion->proposalStatusNeedsUserFeedback()) {
                            $msg = \Yii::t('amend', 'proposal_notify_w_feedback');
                        } else {
                            $msg = \Yii::t('amend', 'proposal_notify_o_feedback');
                        }
                        ?>
                        <button class="notifyProposer hideIfChanged btn btn-xs btn-default" type="button">
                            <?= $msg ?>
                        </button>
                        <div class="showIfChanged notSavedHint">
                            <?= \Yii::t('amend', 'proposal_notify_notsaved') ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <section class="proposalCommentForm">
            <h3><?= \Yii::t('amend', 'proposal_comment_title') ?></h3>
            <ol class="commentList">
                <?php
                foreach ($motion->adminComments as $adminComment) {
                    $user = $adminComment->user;
                    ?>
                    <li>
                        <div class="header">
                            <div class="date"><?= Tools::formatMysqlDateTime($adminComment->dateCreation) ?></div>
                            <div class="name"><?= Html::encode($user ? $user->name : '-') ?></div>
                        </div>
                        <div class="comment"><?= Html::encode($adminComment->text) ?></div>
                    </li>
                    <?php
                }
                ?>
            </ol>

            <textarea name="text" placeholder="<?= Html::encode(\Yii::t('amend', 'proposal_comment_placeh')) ?>"
                      class="form-control" rows="1"></textarea>
            <button class="btn btn-default btn-xs"><?= \Yii::t('amend', 'proposal_comment_write') ?></button>
        </section>
    </div>
    <section class="statusDetails status_<?= Motion::STATUS_OBSOLETED_BY ?>">
        <label class="headingLabel"><?= \Yii::t('amend', 'proposal_obsoleted_by') ?>...</label>
        <?php
        $options = ['-'];
        foreach ($motion->getMyConsultation()->getVisibleMotionsSorted(false) as $otherMotion) {
            if ($otherMotion->id == $motion->id) {
                continue;
            }
            foreach ($otherMotion->getVisibleAmendmentsSorted() as $otherAmend) {
                $options[$otherAmend->id] = $otherAmend->getTitle();
            }
        }
        $attrs = ['id' => 'obsoletedByAmendment', 'class' => 'form-control'];
        echo HTMLTools::fueluxSelectbox('obsoletedByMotion', $options, $preObsoletedBy, $attrs);
        ?>
    </section>
    <section class="statusDetails status_<?= Motion::STATUS_REFERRED ?>">
        <label class="headingLabel" for="referredTo"><?= \Yii::t('amend', 'proposal_refer_to') ?>...</label>
        <input type="text" name="referredTo" id="referredTo" value="<?= Html::encode($preReferredTo) ?>"
               class="form-control">
    </section>
    <section class="statusDetails status_<?= Motion::STATUS_CUSTOM_STRING ?>">
        <label class="headingLabel" for="statusCustomStr"><?= \Yii::t('amend', 'proposal_custom_str') ?>:</label>
        <input type="text" name="statusCustomStr" id="statusCustomStr" value="<?= Html::encode($preCustomStr) ?>"
               class="form-control">
    </section>
    <section class="statusDetails status_<?= Motion::STATUS_VOTE ?>">
        <div class="votingStatus">
            <h3><?= \Yii::t('amend', 'proposal_voting_status') ?></h3>
            <?php
            foreach (Motion::getVotingStati() as $statusId => $statusName) {
                ?>
                <label>
                    <input type="radio" name="votingStatus" value="<?= $statusId ?>" <?php
                    if ($motion->votingStatus == $statusId) {
                        echo 'checked';
                    }
                    ?>> <?= Html::encode($statusName) ?>
                </label><br>
                <?php
            }
            ?>
        </div>
    </section>
    <section class="publicExplanation">
        <h3><?= \Yii::t('amend', 'proposal_public_expl_title') ?></h3>
        <?php
        echo Html::textarea(
            'proposalExplanation',
            $motion->proposalExplanation,
            [
                'title' => \Yii::t('amend', 'proposal_public_expl_title'),
                'class' => 'form-control',
            ]
        );
        ?>
    </section>
    <section class="saving showIfChanged">
        <button class="btn btn-default btn-sm">
            <?= \Yii::t('amend', 'proposal_save_changes') ?>
        </button>
    </section>
    <section class="saved">
        <?= \Yii::t('base', 'saved') ?>
    </section>
<?= Html::endForm() ?>