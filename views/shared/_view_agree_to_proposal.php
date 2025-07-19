<?php

use app\models\db\Amendment;
use app\models\db\ConsultationLog;
use app\models\proposedProcedure\ActivityConsultationLog;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var \app\models\db\IMotion $imotion
 * @var \app\models\db\IProposal $proposal
 * @var string|null $procedureToken
 */

echo Html::beginForm('', 'post', ['class' => 'agreeToProposal']);
$agreed = ($proposal->userStatus === \app\models\db\IMotion::STATUS_ACCEPTED);
$disagreed = ($proposal->userStatus === \app\models\db\IMotion::STATUS_REJECTED);

$notifications = ConsultationLog::getProposalNotification($imotion);

?>
    <h2><?= Yii::t('amend', 'proposal_edit_title_prop') ?></h2>
    <div class="holder">
        <div class="status">
            <div class="head"><?= Yii::t('amend', 'proposal_edit_title_prop') ?></div>
            <div class="description">
                <?= $proposal->getFormattedProposalStatus() ?>
            </div>
            <?php
            if ($imotion->votingBlock) {
                ?>
                <div class="head"><?= Yii::t('amend', 'proposal_voteblock') ?></div>
                <div class="description"><?= Html::encode($imotion->votingBlock->title) ?></div>
                <?php
            }
            ?>
        </div>
        <div>
            <?php
            foreach ($notifications as $notification) {
                if (!$notification->data) {
                    continue;
                }
                $data = new \app\models\consultationLog\ProposedProcedureUserNotification($notification->data);
                echo '<article>';
                echo '<div>' . \app\components\Tools::formatMysqlDateTime($notification->actionTime) . '</div>';
                echo '<blockquote>' . \app\components\HTMLTools::textToHtmlWithLink($data->text) . '</blockquote>';
                echo '</article>';
            }
            ?>
        </div>
    </div>
    <div class="comment">
        <label for="proposalAgreeComment">Kommentar:</label>
        <textarea class="form-control" name="comment" id="proposalAgreeComment">

        </textarea>
    </div>
    <div class="agreement">
        <?php
        if ($agreed) {
            echo '<div>';
            echo '<span class="agreed glyphicon glyphicon-ok" aria-hidden="true"></span> ';
            echo Yii::t('amend', 'proposal_user_agreed');
            echo '</div>';
        } elseif ($disagreed) {
            echo '<div>';
            echo '<span class="agreed glyphicon glyphicon-remove" aria-hidden="true"></span> ';
            echo Yii::t('amend', 'proposal_user_disagreed');
            echo '</div>';
        } else {
            ?>
            <div class="disagree">
                <button type="submit" name="setProposalDisagree" class="btn btn-danger">
                    <?= Yii::t('amend', 'proposal_user_disagree') ?>
                </button>
            </div>
            <div class="agree">
                <button type="submit" name="setProposalAgree" class="btn btn-success">
                    <?= Yii::t('amend', 'proposal_user_agree') ?>
                </button>
            </div>
            <?php
        }
        ?>
    </div>
    <input type="hidden" name="procedureToken" value="<?= Html::encode($procedureToken) ?>">
<?php
echo Html::endForm();
