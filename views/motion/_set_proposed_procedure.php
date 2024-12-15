<?php

/**
 * @var Yii\web\View $this
 * @var Motion $motion
 * @var string $context
 * @var string $saveUrl
 */

use app\models\settings\Privileges;
use app\components\{HTMLTools, IMotionStatusFilter, Tools, UrlHelper};
use app\models\db\{IAdminComment, Motion, User};
use yii\helpers\Html;

$saveUrl = UrlHelper::createMotionUrl($motion, 'save-proposal-status');
echo Html::beginForm($saveUrl, 'POST', [
    'id'                       => 'proposedChanges',
    'class'                    => 'version' . $motion->version,
    'data-antragsgruen-widget' => 'backend/ChangeProposedProcedure',
    'data-context'             => $context,
]);
if ($motion->proposalStatus === Motion::STATUS_REFERRED) {
    $preReferredTo = $motion->proposalComment;
} else {
    $preReferredTo = '';
}
if ($motion->proposalStatus === Motion::STATUS_OBSOLETED_BY_AMENDMENT) {
    $preObsoletedBy = $motion->proposalComment;
} else {
    $preObsoletedBy = '';
}
if ($motion->proposalStatus === Motion::STATUS_CUSTOM_STRING) {
    $preCustomStr = $motion->proposalComment;
} else {
    $preCustomStr = '';
}

if (isset($msgAlert)) {
    echo '<div class="alert alert-info">' . $msgAlert . '</div>';
}

$consultation = $motion->getMyConsultation();
$votingBlocks = $consultation->votingBlocks;
$allTags = $consultation->getSortedTags(\app\models\db\ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE);
$selectedTags = $motion->getProposedProcedureTags();
$currBlockIsLocked = ($motion->votingBlock && !$motion->votingBlock->itemsCanBeRemoved());
$canBeChangedUnlimitedly = $motion->canEditProposedProcedure();
$limitedDisabled = ($canBeChangedUnlimitedly ? null : true);
$voting = $motion->getVotingData();
?>
<h2>
    <?= Yii::t('amend', 'proposal_amend_title') ?>
    <button class="pull-right btn-link closeBtn" type="button"
            title="<?= Html::encode(Yii::t('amend', 'proposal_close')) ?>">
        <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
        <span class="sr-only"><?= Html::encode(Yii::t('amend', 'proposal_close')) ?></span>
    </button>
</h2>
<div class="holder">
    <fieldset class="statusForm">
        <legend class="hidden"><?= Yii::t('amend', 'proposal_status_title') ?></legend>
        <h3><?= Yii::t('amend', 'proposal_status_title') ?></h3>

        <?php
        $foundStatus = false;
        foreach ($consultation->getStatuses()->getMotionProposedProcedureStatuses() as $statusId => $statusName) {
            ?>
            <label class="proposalStatus<?= $statusId ?>">
                <input type="radio" name="proposalStatus" value="<?= $statusId ?>"<?php
                if (intval($motion->proposalStatus) === intval($statusId)) {
                    $foundStatus = true;
                    echo ' checked';
                }
                if (!$canBeChangedUnlimitedly) {
                    echo ' disabled';
                }
                ?>> <?= Html::encode($statusName) ?>
            </label><br>
            <?php
        }
        ?>
        <label>
            <?= Html::radio('proposalStatus', !$foundStatus, ['value' => '0', 'disabled' => $limitedDisabled]) ?>
            - <?= Yii::t('amend', 'proposal_status_na') ?> -
        </label>
    </fieldset>
    <div class="middleCol">
        <fieldset class="visibilitySettings showIfStatusSet">
            <legend class="hidden"><?= Yii::t('amend', 'proposal_publicity') ?></legend>
            <h3><?= Yii::t('amend', 'proposal_publicity') ?></h3>
            <label>
                <?= Html::checkbox('proposalVisible', ($motion->proposalVisibleFrom !== null), ['disabled' => $limitedDisabled]) ?>
                <?= Yii::t('amend', 'proposal_visible') ?>
            </label>
            <label>
                <?= Html::checkbox('setPublicExplanation', ($motion->proposalExplanation !== null), ['disabled' => $limitedDisabled]) ?>
                <?= Yii::t('amend', 'proposal_public_expl_set') ?>
            </label>
        </fieldset>
        <div class="votingBlockSettings showIfStatusSet">
            <h3><?= Yii::t('amend', 'proposal_voteblock') ?></h3>
            <select name="votingBlockId" id="votingBlockId" class="stdDropdown">
                <option>-</option>
                <?php
                foreach ($votingBlocks as $votingBlock) {
                    echo '<option value="' . Html::encode($votingBlock->id) . '"';
                    if ($motion->votingBlockId === $votingBlock->id) {
                        echo ' selected';
                    }
                    echo '>' . Html::encode($votingBlock->title) . '</option>';
                }
                ?>
                <option value="NEW">- <?= Yii::t('amend', 'proposal_voteblock_newopt') ?> -</option>
            </select>
            <?php
            if (User::getCurrentUser() && User::getCurrentUser()->hasPrivilege($consultation, Privileges::PRIVILEGE_VOTINGS, null)) {
                $url = UrlHelper::createUrl(['consultation/admin-votings']);
                $title = Html::encode(Yii::t('amend', 'proposal_voteblock_edit'));
                ?>
                <a href="<?= Html::encode($url) ?>" class="votingEditLink" title="<?= $title ?>">
                    <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
                    <span class="sr-only"><?= $title ?></span>
                </a>
                <?php
            }
            ?>
            <div class="newBlock">
                <label for="newBlockTitle" class="control-label">
                    <?= Yii::t('amend', 'proposal_voteblock_new') ?>:
                </label>
                <input type="text" class="form-control" id="newBlockTitle" name="newBlockTitle">
            </div>
            <?php
                foreach ($votingBlocks as $votingBlock) {
                    $subitems = $votingBlock->getVotingItemBlocks(true, $motion);
                    if (count($subitems) === 0) {
                        continue;
                    }
                    ?>
                    <div class="votingItemBlockRow votingItemBlockRow<?= $votingBlock->id ?>">
                        <label class="control-label" for="votingItemBlockId<?= $votingBlock->id ?>">
                            <?= Yii::t('amend', 'proposal_voteitemblock') ?>
                        </label>
                        <select name="votingItemBlockId[<?= $votingBlock->id ?>]" id="votingItemBlockId<?= $votingBlock->id ?>"
                                class="stdDropdown votingItemBlockInput" data-voting-block="<?= $votingBlock->id ?>">
                            <option value=""><?= Yii::t('amend', 'proposal_voteitemblock_none') ?></option>
                            <?php
                            foreach ($subitems as $subitem) {
                                echo '<option value="' . $subitem->groupId . '"';
                                if (in_array($motion->id, $subitem->motionIds)) {
                                    echo ' selected';
                                }
                                echo ' data-group-name="' . Html::encode($subitem->groupName ?: '') . '"';
                                echo '>' . Html::encode($subitem->getTitle($motion)) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <?php
                }
                ?>
            <div class="votingItemBlockNameRow votingItemBlockNameRow">
                <label class="control-label" for="votingItemBlockName">
                    <?= Yii::t('amend', 'proposal_voteitemblock_name') ?>:
                </label>
                <input name="votingItemBlockName" id="votingItemBlockName"
                       class="form-control" value="<?= Html::encode($voting->itemGroupName ?: '') ?>"
                    <?= ($currBlockIsLocked ? ' disabled' : '') ?>>
            </div>
        </div>
        <div class="notificationSettings showIfStatusSet">
            <h3><?= Yii::t('amend', 'proposal_noti') ?></h3>
            <div class="notificationStatus">
                <?php
                if ($motion->proposalUserStatus !== null) {
                    if ($motion->proposalUserStatus === Motion::STATUS_ACCEPTED) {
                        echo '<span class="glyphicon glyphicon glyphicon-ok accepted" aria-hidden="true"></span>';
                        echo Yii::t('amend', 'proposal_user_accepted');
                    } elseif ($motion->proposalUserStatus === Motion::STATUS_REJECTED) {
                        echo '<span class="glyphicon glyphicon glyphicon-remove rejected" aria-hidden="true"></span>';
                        echo Yii::t('amend', 'proposal_user_rejected');
                    } else {
                        echo 'Error: unknown response of the proposer';
                    }
                } elseif ($motion->proposalFeedbackHasBeenRequested()) {
                    $msg  = Yii::t('amend', 'proposal_notified');
                    $date = Tools::formatMysqlDate($motion->proposalNotification, false);
                    echo str_replace('%DATE%', $date, $msg);
                    echo ' ' . Yii::t('amend', 'proposal_no_feedback');

                    ?>
                    <div class="setConfirmationStatus">
                        <button class="btn btn-xs btn-link setConfirmation" type="button"
                                data-msg="<?= Html::encode(Yii::t('amend', 'proposal_set_feedback_conf')) ?>">
                            <?= Yii::t('amend', 'proposal_set_feedback') ?>
                        </button>
                        <button class="btn btn-xs btn-link sendAgain" type="button"
                                data-msg="<?= Html::encode(Yii::t('amend', 'proposal_send_again_conf')) ?>">
                            <?= Yii::t('amend', 'proposal_send_again') ?>
                        </button>
                    </div>
                    <?php
                } elseif ($motion->proposalStatus !== null) {
                    if ($motion->proposalAllowsUserFeedback()) {
                        $msg = Yii::t('amend', 'proposal_notify_w_feedback');
                    } else {
                        $msg = Yii::t('amend', 'proposal_notify_o_feedback');
                    }
                    ?>
                    <button class="notifyProposer hideIfChanged btn btn-xs btn-default" type="button">
                        <?= $msg ?>
                    </button>
                    <div class="showIfChanged notSavedHint">
                        <?= Yii::t('amend', 'proposal_notify_notsaved') ?>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
    <section class="proposalCommentForm">
        <h3><?= Yii::t('amend', 'proposal_comment_title') ?></h3>
        <ol class="commentList">
            <?php
            $commentTypes = [IAdminComment::TYPE_PROPOSED_PROCEDURE];
            foreach ($motion->getAdminComments($commentTypes, IAdminComment::SORT_ASC) as $adminComment) {
                $user = $adminComment->getMyUser();
                ?>
                <li class="comment" data-id="<?= $adminComment->id ?>">
                    <div class="header">
                        <div class="date"><?= Tools::formatMysqlDateTime($adminComment->dateCreation) ?></div>
                        <?php
                        if (User::isCurrentUser($user)) {
                            $url = UrlHelper::createMotionUrl($motion, 'del-proposal-comment');
                            echo '<button type="button" data-url="' . Html::encode($url) . '" class="btn-link delComment">';
                            echo '<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>';
                            echo '<span class="sr-only">' . Yii::t('amend', 'proposal_comment_delete') . '</span>';
                            echo '</button>';
                        }
                        ?>
                        <div class="name"><?= Html::encode($user ? $user->name : '-') ?></div>
                    </div>
                    <div class="comment">
                        <?php
                        if ($adminComment->status === IAdminComment::TYPE_PROPOSED_PROCEDURE) {
                            echo '<div class="overv">' . Yii::t('amend', 'proposal_comment_overview') . '</div>';
                        }
                        ?>
                        <?= HTMLTools::textToHtmlWithLink($adminComment->text) ?>
                    </div>
                </li>
                <?php
            }
            ?>
        </ol>

        <textarea name="text" placeholder="<?= Html::encode(Yii::t('amend', 'proposal_comment_placeh')) ?>"
                  title="<?= Html::encode(Yii::t('amend', 'proposal_comment_placeh')) ?>"
                  class="form-control" rows="1"></textarea>
        <button class="btn btn-default btn-xs"><?= Yii::t('amend', 'proposal_comment_write') ?></button>
    </section>
</div>
<section class="proposalTags">
    <label for="proposalTagsSelect"><?= Yii::t('amend', 'proposal_tags') ?>:</label>
    <div class="selectize-wrapper">
        <select class="proposalTagsSelect" name="proposalTags[]" multiple="multiple" id="proposalTagsSelect">
            <?php
            foreach ($allTags as $tag) {
                echo '<option name="' . Html::encode($tag->title) . '"';
                if (isset($selectedTags[$tag->getNormalizedName()])) {
                    echo ' selected';
                }
                echo '>' . Html::encode($tag->title) . '</option>';
            }
            ?>
        </select>
    </div>
</section>
<section class="statusDetails status_<?= Motion::STATUS_OBSOLETED_BY_AMENDMENT ?>">
    <label class="headingLabel"><?= Yii::t('amend', 'proposal_obsoleted_by') ?>...</label>
    <?php
    $options = ['-'];
    $filter = IMotionStatusFilter::onlyUserVisible($consultation, false);
    foreach ($filter->getFilteredConsultationIMotionsSorted() as $otherMotion) {
        if ($otherMotion->id === $motion->id) {
            continue;
        }
        if (!is_a($otherMotion, Motion::class)) {
            continue;
        }
        foreach ($otherMotion->getVisibleAmendmentsSorted() as $otherAmend) {
            $options[$otherAmend->id] = $otherAmend->getTitle();
        }
    }
    $attrs = ['id' => 'obsoletedByAmendment', 'disabled' => $limitedDisabled];
    echo Html::dropDownList('obsoletedByMotion', $preObsoletedBy, $options, $attrs);
    ?>
</section>
<section class="statusDetails status_<?= Motion::STATUS_REFERRED ?>">
    <label class="headingLabel" for="referredTo"><?= Yii::t('amend', 'proposal_refer_to') ?>...</label>
    <input type="text" name="referredTo" id="referredTo" value="<?= Html::encode($preReferredTo) ?>"
        <?php if (!$canBeChangedUnlimitedly) echo 'disabled'; ?>
           class="form-control">
</section>
<section class="statusDetails status_<?= Motion::STATUS_CUSTOM_STRING ?>">
    <label class="headingLabel" for="statusCustomStr"><?= Yii::t('amend', 'proposal_custom_str') ?>:</label>
    <input type="text" name="statusCustomStr" id="statusCustomStr" value="<?= Html::encode($preCustomStr) ?>"
        <?php if (!$canBeChangedUnlimitedly) echo 'disabled'; ?>
           class="form-control">
</section>
<section class="statusDetails status_<?= Motion::STATUS_VOTE ?>">
    <div class="votingStatus">
        <h3><?= Yii::t('amend', 'proposal_voting_status') ?></h3>
        <?php
        foreach ($consultation->getStatuses()->getVotingStatuses() as $statusId => $statusName) {
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
    <h3><?= Yii::t('amend', 'proposal_public_expl_title') ?></h3>
    <?php
    echo Html::textarea(
        'proposalExplanation',
        $motion->proposalExplanation ?: '',
        [
            'title' => Yii::t('amend', 'proposal_public_expl_title'),
            'class' => 'form-control',
            'disabled' => $limitedDisabled,
        ]
    );
    ?>
</section>
<section class="notifyProposerSection hidden">
    <h3><?= Yii::t('amend', 'proposal_notify_text') ?></h3>
    <div class="proposalFrom">
        <?php
        $replyTo = \app\components\mail\Tools::getDefaultReplyTo($motion, $consultation, User::getCurrentUser());
        $fromName = \app\components\mail\Tools::getDefaultMailFromName($consultation);
        $placeholderReplyTo = Yii::t('amend', 'proposal_notify_replyto') . ': ' . $replyTo;
        $placeholderName = Yii::t('amend', 'proposal_notify_name') . ': ' . $fromName;
        ?>
        <div>
            <input type="text" name="proposalNotificationFrom" id="proposalNotificationFrom" class="form-control"
                   title="<?= Yii::t('amend', 'proposal_notify_name') ?>"
                   placeholder="<?= Html::encode($placeholderName) ?>">
        </div>
        <div>
            <input type="text" name="proposalNotificationReply" id="proposalNotificationReply" class="form-control"
                   title="<?= Yii::t('amend', 'proposal_notify_replyto') ?>"
                   placeholder="<?= Html::encode($placeholderReplyTo) ?>">
        </div>
    </div>
    <?php
    $defaultText = \app\models\notifications\MotionProposedProcedure::getDefaultText($motion);
    echo Html::textarea(
        'proposalNotificationText',
        $defaultText,
        [
            'title' => Yii::t('amend', 'proposal_notify_text'),
            'class' => 'form-control',
            'rows'  => 5,
        ]
    );
    ?>
    <div class="submitRow">
        <button type="button" name="notificationSubmit" class="btn btn-success btn-sm">
            <?php
            if ($motion->proposalAllowsUserFeedback()) {
                echo Yii::t('amend', 'proposal_notify_w_feedback');
            } else {
                echo Yii::t('amend', 'proposal_notify_o_feedback');
            }
            ?>
        </button>
    </div>
</section>
<section class="saving showIfChanged">
    <button class="btn btn-primary btn-sm">
        <?= Yii::t('amend', 'proposal_save_changes') ?>
    </button>
</section>
<section class="saved">
    <?= Yii::t('base', 'saved') ?>
</section>
<?php
if ($context !== 'edit' && $canBeChangedUnlimitedly) {
    $classes   = ['statusDetails'];
    $classes[] = 'status_' . Motion::STATUS_MODIFIED_ACCEPTED;
    $classes[] = 'status_' . Motion::STATUS_VOTE;
    ?>
    <section class="<?= implode(' ', $classes) ?>">
        <h3><?= Yii::t('amend', 'proposal_modified_accepted') ?></h3>
        <?php
        echo Html::a(
            Yii::t('base', 'edit'),
            UrlHelper::createMotionUrl($motion, 'edit-proposed-change'),
            ['class' => 'editModification']
        );
        ?>
    </section>
    <?php
}
?>
<?= Html::endForm() ?>
