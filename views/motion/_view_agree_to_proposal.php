<?php

use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Motion $motion
 */

echo Html::beginForm('', 'post', ['class' => 'agreeToProposal']);
$agreed = ($motion->proposalUserStatus == \app\models\db\Motion::STATUS_ACCEPTED);
?>
    <h2><?= Yii::t('amend', 'proposal_edit_title_prop') ?></h2>
    <div class="holder">
        <div class="status">
            <div class="head"><?= Yii::t('amend', 'proposal_edit_title_prop') ?></div>
            <div class="description">
                <?= $motion->getFormattedProposalStatus() ?>
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
                echo '<span class="agreed glyphicon glyphicon-ok"></span> ';
                echo Yii::t('amend', 'proposal_user_agree');
            } else {
                ?>
                <button type="submit" name="setProposalAgree" class="btn btn-success">
                    <?= Yii::t('amend', 'proposal_user_agree') ?>
                </button>
                <?php
            }
            ?>
        </div>
    </div>
    <div class="hint">
        <?= Yii::t('amend', 'proposal_user_disagree_h') ?>
    </div>

<?php
echo Html::endForm();

