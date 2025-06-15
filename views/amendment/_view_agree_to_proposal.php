<?php

use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var \app\models\db\Amendment $amendment
 * @var string|null $procedureToken
 */

echo Html::beginForm('', 'post', ['class' => 'agreeToProposal']);
$agreed = ($amendment->proposalUserStatus === \app\models\db\Amendment::STATUS_ACCEPTED);
$disagreed = ($amendment->proposalUserStatus === \app\models\db\Amendment::STATUS_REJECTED);
?>
    <h2><?= Yii::t('amend', 'proposal_edit_title_prop') ?></h2>
    <div class="holder">
        <div class="status">
            <div class="head"><?= Yii::t('amend', 'proposal_edit_title_prop') ?></div>
            <div class="description">
                <?= $amendment->getFormattedProposalStatus() ?>
            </div>
            <?php
            if ($amendment->votingBlock) {
                ?>
                <div class="head"><?= Yii::t('amend', 'proposal_voteblock') ?></div>
                <div class="description"><?= Html::encode($amendment->votingBlock->title) ?></div>
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
