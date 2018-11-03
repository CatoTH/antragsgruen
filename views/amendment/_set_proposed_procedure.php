<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment $amendment
 * @var string $context
 */

use app\components\HTMLTools;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\IAdminComment;
use yii\helpers\Html;

$collidingAmendments = $amendment->collidesWithOtherProposedAmendments(true);

$saveUrl = \app\components\UrlHelper::createAmendmentUrl($amendment, 'save-proposal-status');
echo Html::beginForm($saveUrl, 'POST', [
    'id'                       => 'proposedChanges',
    'data-antragsgruen-widget' => 'backend/ChangeProposedProcedure',
    'data-context'             => $context,
    'class'                    => 'fuelux',
]);
if ($amendment->proposalStatus == Amendment::STATUS_REFERRED) {
    $preReferredTo = $amendment->proposalComment;
} else {
    $preReferredTo = '';
}
if ($amendment->proposalStatus == Amendment::STATUS_OBSOLETED_BY) {
    $preObsoletedBy = $amendment->proposalComment;
} else {
    $preObsoletedBy = '';
}
if ($amendment->proposalStatus == Amendment::STATUS_CUSTOM_STRING) {
    $preCustomStr = $amendment->proposalComment;
} else {
    $preCustomStr = '';
}

if (isset($msgAlert) && $msgAlert !== null) {
    echo '<div class="alert alert-info">' . $msgAlert . '</div>';
}

$votingBlocks = $amendment->getMyConsultation()->votingBlocks;
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
            foreach (Amendment::getProposedChangeStatuses() as $statusId) {
                ?>
                <label class="proposalStatus<?= $statusId ?>">
                    <input type="radio" name="proposalStatus" value="<?= $statusId ?>" <?php
                    if ($amendment->proposalStatus == $statusId) {
                        $foundStatus = true;
                        echo 'checked';
                    }
                    ?>> <?= Amendment::getProposedStatusNames()[$statusId] ?>
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
                    <?= Html::checkbox('proposalVisible', ($amendment->proposalVisibleFrom !== null)) ?>
                    <?= \Yii::t('amend', 'proposal_visible') ?>
                </label>
                <label>
                    <?= Html::checkbox('setPublicExplanation', ($amendment->proposalExplanation !== null)) ?>
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
                $attrs          = ['id' => 'votingBlockId'];
                echo HTMLTools::fueluxSelectbox('votingBlockId', $options, $amendment->votingBlockId, $attrs);
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
                    if ($amendment->proposalUserStatus !== null) {
                        if ($amendment->proposalUserStatus == Amendment::STATUS_ACCEPTED) {
                            echo '<span class="glyphicon glyphicon glyphicon-ok accepted"></span>';
                            echo \Yii::t('amend', 'proposal_user_accepted');
                        } elseif ($amendment->proposalUserStatus == Amendment::STATUS_REJECTED) {
                            echo '<span class="glyphicon glyphicon glyphicon-remove rejected"></span>';
                            echo \Yii::t('amend', 'proposal_user_rejected');
                        } else {
                            echo 'Error: unknown response of the proposer';
                        }
                    } elseif ($amendment->proposalFeedbackHasBeenRequested()) {
                        $msg  = \Yii::t('amend', 'proposal_notified');
                        $date = Tools::formatMysqlDate($amendment->proposalNotification, null, false);
                        echo str_replace('%DATE%', $date, $msg);
                        echo ' ' . \Yii::t('amend', 'proposal_no_feedback');

                        echo '<div class="setConfirmationStatus">';
                        echo '<button class="btn btn-xs btn-link setConfirmation" type="button"
                                      data-msg="' . Html::encode(\Yii::t('amend', 'proposal_set_feedback_conf')) . '">';
                        echo \Yii::t('amend', 'proposal_set_feedback');
                        echo '</button>';
                        echo '</div>';
                    } elseif ($amendment->proposalStatus !== null) {
                        if ($amendment->proposalAllowsUserFeedback()) {
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
                $commentTypes = [IAdminComment::PROPOSED_PROCEDURE];
                foreach ($amendment->getAdminComments($commentTypes, IAdminComment::SORT_ASC) as $adminComment) {
                    $user = $adminComment->user;
                    ?>
                    <li class="comment" data-id="<?= $adminComment->id ?>">
                        <div class="header">
                            <div class="date"><?= Tools::formatMysqlDateTime($adminComment->dateCreation) ?></div>
                            <?php
                            if (\app\models\db\User::isCurrentUser($adminComment->user)) {
                                $url = UrlHelper::createAmendmentUrl($amendment, 'del-proposal-comment');
                                echo '<button type="button" data-url="' . Html::encode($url) . '" ';
                                echo 'class="btn-link delComment">';
                                echo '<span class="glyphicon glyphicon-trash"></span></button>';
                            }
                            ?>
                            <div class="name"><?= Html::encode($user ? $user->name : '-') ?></div>
                        </div>
                        <div class="comment">
                            <?php
                            if ($adminComment->status == IAdminComment::PROPOSED_PROCEDURE) {
                                echo '<div class="overv">' . \Yii::t('amend', 'proposal_comment_overview') . '</div>';
                            }
                            ?>
                            <?= HTMLTools::textToHtmlWithLink($adminComment->text) ?>
                        </div>
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
    <section class="statusDetails status_<?= Amendment::STATUS_OBSOLETED_BY ?>">
        <label class="headingLabel"><?= \Yii::t('amend', 'proposal_obsoleted_by') ?>...</label>
        <?php
        $options = ['-'];
        foreach ($amendment->getMyMotion()->getVisibleAmendmentsSorted() as $otherAmend) {
            if ($otherAmend->id != $amendment->id) {
                $options[$otherAmend->id] = $otherAmend->getTitle();
            }
        }
        foreach ($amendment->getMyConsultation()->getVisibleMotionsSorted(false) as $otherMotion) {
            if ($otherMotion->id == $amendment->motionId) {
                continue;
            }
            foreach ($otherMotion->getVisibleAmendmentsSorted() as $otherAmend) {
                $options[$otherAmend->id] = $otherAmend->getTitle();
            }
        }
        $attrs = ['id' => 'obsoletedByAmendment'];
        echo HTMLTools::fueluxSelectbox('obsoletedByAmendment', $options, $preObsoletedBy, $attrs);
        ?>
    </section>
    <section class="statusDetails status_<?= Amendment::STATUS_REFERRED ?>">
        <label class="headingLabel" for="referredTo"><?= \Yii::t('amend', 'proposal_refer_to') ?>...</label>
        <input type="text" name="referredTo" id="referredTo" value="<?= Html::encode($preReferredTo) ?>"
               class="form-control">
    </section>
    <section class="statusDetails status_<?= Amendment::STATUS_CUSTOM_STRING ?>">
        <label class="headingLabel" for="statusCustomStr"><?= \Yii::t('amend', 'proposal_custom_str') ?>:</label>
        <input type="text" name="statusCustomStr" id="statusCustomStr" value="<?= Html::encode($preCustomStr) ?>"
               class="form-control">
    </section>
    <section class="statusDetails status_<?= Amendment::STATUS_VOTE ?>">
        <div class="votingStatus">
            <h3><?= \Yii::t('amend', 'proposal_voting_status') ?></h3>
            <?php
            foreach (Amendment::getVotingStatuses() as $statusId => $statusName) {
                ?>
                <label>
                    <input type="radio" name="votingStatus" value="<?= $statusId ?>" <?php
                    if ($amendment->votingStatus == $statusId) {
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
            $amendment->proposalExplanation,
            [
                'title' => \Yii::t('amend', 'proposal_public_expl_title'),
                'class' => 'form-control',
            ]
        );
        ?>
    </section>
    <section class="collisions <?= (count($collidingAmendments) === 0 ? 'hidden' : '') ?>">
        <h3><?= \Yii::t('amend', 'proposal_conflict_title') ?>:</h3>
        <ul>
            <?php
            foreach ($collidingAmendments as $collidingAmendment) {
                $title = $collidingAmendment->getShortTitle();
                $url   = UrlHelper::createAmendmentUrl($collidingAmendment);
                echo '<li class="collision' . $collidingAmendment->id . '">' . Html::a($title, $url);
                if ($collidingAmendment->proposalStatus == Amendment::STATUS_VOTE) {
                    echo ' (' . \Yii::t('amend', 'proposal_voting') . ')';
                }
                echo '</li>';
            }
            ?>
        </ul>
    </section>
    <section class="notifyProposerSection hidden">
        <h3><?= \Yii::t('amend', 'proposal_notify_text') ?></h3>
        <div class="row proposalFrom">
            <?php
            $placeholderReplyTo = \Yii::t('amend', 'proposal_notify_replyto') . ': ' .
                \app\components\mail\Tools::getDefaultReplyTo($amendment->getMyConsultation());
            $placeholderName = \Yii::t('amend', 'proposal_notify_name') . ': ' .
                \app\components\mail\Tools::getDefaultMailFromName($amendment->getMyConsultation());
            ?>
            <div class="col-md-6">
                <input type="text" name="proposalNotificationFrom" id="proposalNotificationFrom" class="form-control"
                       title="<?= \Yii::t('amend', 'proposal_notify_name') ?>"
                       placeholder="<?= Html::encode($placeholderName) ?>">
            </div>
            <div class="col-md-6">
                <input type="text" name="proposalNotificationReply" id="proposalNotificationReply" class="form-control"
                       title="<?= \Yii::t('amend', 'proposal_notify_replyto') ?>"
                       placeholder="<?= Html::encode($placeholderReplyTo) ?>">
            </div>
        </div>
        <?php
        $defaultText = \app\models\notifications\AmendmentProposedProcedure::getDefaultText($amendment);
        echo Html::textarea(
            'proposalNotificationText',
            $defaultText,
            [
                'title' => \Yii::t('amend', 'proposal_notify_text'),
                'class' => 'form-control',
                'rows'  => 5,
            ]
        );
        ?>
        <div class="submitRow">
            <button type="button" name="notificationSubmit" class="btn btn-success btn-sm">
                <?php
                if ($amendment->proposalAllowsUserFeedback()) {
                    echo \Yii::t('amend', 'proposal_notify_w_feedback');
                } else {
                    echo \Yii::t('amend', 'proposal_notify_o_feedback');
                }
                ?>
            </button>
        </div>
    </section>
    <section class="saving showIfChanged">
        <button class="btn btn-default btn-sm">
            <?= \Yii::t('amend', 'proposal_save_changes') ?>
        </button>
    </section>
    <section class="saved">
        <?= \Yii::t('base', 'saved') ?>
    </section>
<?php
if ($context !== 'edit') {
    $classes   = ['statusDetails'];
    $classes[] = 'status_' . Amendment::STATUS_MODIFIED_ACCEPTED;
    $classes[] = 'status_' . Amendment::STATUS_VOTE;
    ?>
    <section class="<?= implode(' ', $classes) ?>">
        <h3><?= \Yii::t('amend', 'proposal_modified_accepted') ?></h3>
        <?php
        echo Html::a(
            \Yii::t('base', 'edit'),
            UrlHelper::createAmendmentUrl($amendment, 'edit-proposed-change'),
            ['class' => 'editModification']
        );
        ?>
    </section>
    <?php
}
?>
<?= Html::endForm() ?>
