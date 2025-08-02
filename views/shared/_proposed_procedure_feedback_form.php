<?php

/**
 * @var Yii\web\View $this
 * @var IMotion $imotion
 * @var IProposal $proposal
 * @var string $defaultText
 */

use app\models\db\{IMotion, IProposal, User};
use yii\helpers\Html;

$consultation = $imotion->getMyConsultation();
?>
<section class="notifyProposerSection hidden">
    <h3><?= Yii::t('amend', 'proposal_notify_text') ?></h3>
    <div class="proposalFrom">
        <?php
        $replyTo = \app\components\mail\Tools::getDefaultReplyTo($imotion, $consultation, User::getCurrentUser());
        $fromName = \app\components\mail\Tools::getDefaultMailFromName($consultation);
        $placeholderReplyTo = Yii::t('amend', 'proposal_notify_replyto') . ': ' . ($replyTo ?: '-');
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
            if ($proposal->proposalAllowsUserFeedback()) {
                echo Yii::t('amend', 'proposal_notify_w_feedback');
            } else {
                echo Yii::t('amend', 'proposal_notify_o_feedback');
            }
            ?>
        </button>
    </div>
</section>
