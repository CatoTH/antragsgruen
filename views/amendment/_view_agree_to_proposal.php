<?php

use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment $amendment
 */

echo Html::beginForm('', 'post', ['class' => 'agreeToProposal']);
$agreed = ($amendment->proposalUserStatus == \app\models\db\Amendment::STATUS_ACCEPTED);
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
            <?php
            if ($agreed) {
                echo '<span class="agreed glyphicon glyphicon-ok"></span> ';
                echo \Yii::t('amend', 'proposal_user_agree');
            } else {
                ?>
                <button type="submit" name="setProposalAgree" class="btn btn-success">
                    <?= \Yii::t('amend', 'proposal_user_agree') ?>
                </button>
                <?php
            }
            ?>
        </div>
    </div>

<?php
echo Html::endForm();

