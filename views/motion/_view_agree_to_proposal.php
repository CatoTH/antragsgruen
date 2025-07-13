<?php

use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Motion $motion
 * @var \app\models\db\MotionProposal $proposal
 * @var string|null $procedureToken
 */

echo Html::beginForm('', 'post', ['class' => 'agreeToProposal']);
$agreed = ($proposal->userStatus === \app\models\db\Motion::STATUS_ACCEPTED);
$disagreed = ($proposal->userStatus === \app\models\db\Motion::STATUS_REJECTED);
?>
    <h2><?= Yii::t('amend', 'proposal_edit_title_prop') ?></h2>
    <div class="holder">
        <div class="status">
            <div class="head"><?= Yii::t('amend', 'proposal_edit_title_prop') ?></div>
            <div class="description">
                <?= $proposal->getFormattedProposalStatus() ?>
            </div>
            <?php
            if ($motion->votingBlock) {
                ?>
                <div class="head"><?= Yii::t('amend', 'proposal_voteblock') ?></div>
                <div class="description"><?= Html::encode($motion->votingBlock->title) ?></div>
                <?php
            }
            ?>
        </div>
        <div class="agreement">
            <?php
            if ($agreed) {
                echo '<span class="agreed glyphicon glyphicon-ok" aria-hidden="true"></span> ';
                echo Yii::t('amend', 'proposal_user_agreed');
            } elseif ($disagreed) {
                echo '<span class="agreed glyphicon glyphicon-remove" aria-hidden="true"></span> ';
                echo Yii::t('amend', 'proposal_user_disagreed');
            } else {
                ?>
                <button type="submit" name="setProposalAgree" class="btn btn-success">
                    <?= Yii::t('amend', 'proposal_user_agree') ?>
                </button>
                <button type="submit" name="setProposalDisagree" class="btn btn-danger">
                    <?= Yii::t('amend', 'proposal_user_disagree') ?>
                </button>
                <?php
            }
            ?>
        </div>
    </div>
    <input type="hidden" name="procedureToken" value="<?= Html::encode($procedureToken) ?>">
<?php
echo Html::endForm();
