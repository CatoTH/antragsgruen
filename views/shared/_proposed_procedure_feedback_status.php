<?php

/**
 * @var Yii\web\View $this
 * @var IMotion $imotion
 * @var IProposal $proposal
 */

use app\components\Tools;
use app\models\db\{IMotion, IProposal};
use yii\helpers\Html;

$consultation = $imotion->getMyConsultation();
?>

<div class="notificationSettings showIfStatusSet">
    <h3><?= Yii::t('amend', 'proposal_noti') ?></h3>
    <div class="notificationStatus">
        <?php
        if ($proposal->userStatus !== null) {
            if ($proposal->userStatus === IMotion::STATUS_ACCEPTED) {
                echo '<span class="glyphicon glyphicon glyphicon-ok accepted" aria-hidden="true"></span>';
                echo Yii::t('amend', 'proposal_user_accepted');
            } elseif ($proposal->userStatus === IMotion::STATUS_REJECTED) {
                echo '<span class="glyphicon glyphicon glyphicon-remove rejected" aria-hidden="true"></span>';
                echo Yii::t('amend', 'proposal_user_rejected');
            } else {
                echo 'Error: unknown response of the proposer';
            }
        } elseif ($proposal->proposalFeedbackHasBeenRequested()) {
            $msg  = Yii::t('amend', 'proposal_notified');
            $date = Tools::formatMysqlDateTime($proposal->notifiedAt, false);
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
        } elseif ($proposal->proposalStatus !== null) {
            if ($proposal->proposalAllowsUserFeedback()) {
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
