<?php

use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment $amendment
 */

echo Html::beginForm('', 'post', ['class' => 'agreeToProposal']);
if ($amendment->proposalUserStatus == \app\models\db\Amendment::STATUS_ACCEPTED) {
    $agreed = true;
} elseif ($amendment->proposalUserStatus == \app\models\db\Amendment::STATUS_REJECTED) {
    $agreed = false;
} else {
    $agreed = null;
}
?>
    <h2><?= \Yii::t('amend', 'proposal_edit_title_prop') ?></h2>
    <div class="holder">
        <div class="status">
            <div class="head"><?= \Yii::t('amend', 'proposal_edit_title_prop') ?></div>
            <div class="description">
                <?= $amendment->getFormattedProposalStatus() ?>
            </div>
            <?php
            if ($amendment->votingBlock) {
                ?>
                <div class="head"><?= \Yii::t('amend', 'proposal_voteblock') ?></div>
                <div class="description"><?= Html::encode($amendment->votingBlock->title) ?></div>
                <?php
            }
            ?>
        </div>
        <div class="agreement">
            <label class="<?= ($agreed === true ? 'active' : 'inactive') ?>">
                <?= Html::radio('proposalAgreed', ($agreed === true), ['value' => 1, 'required' => 'required']) ?>
                <?= \Yii::t('amend', 'proposal_user_agree') ?>
            </label>
            <label class="<?= ($agreed === false ? 'active' : 'inactive') ?>">
                <?= Html::radio('proposalAgreed', ($agreed === false), ['value' => 0, 'required' => 'required']) ?>
                <?= \Yii::t('amend', 'proposal_user_disagree') ?>
            </label>
            <button type="submit" name="setProposalUserStatus" class="btn btn-success">
                <?= \Yii::t('base', 'save') ?>
            </button>
        </div>
    </div>

<?php
echo Html::endForm();

