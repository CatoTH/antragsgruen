<?php

use app\components\HTMLTools;
use app\components\Tools;
use app\models\consultationLog\ProposedProcedureAgreement;
use app\models\consultationLog\ProposedProcedureUserNotification;
use app\models\db\{ConsultationLog, IMotion, IProposal};
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var IMotion $imotion
 * @var IProposal $proposal
 * @var string|null $procedureToken
 */

echo Html::beginForm('', 'post', ['class' => 'agreeToProposal']);
$agreed = ($proposal->userStatus === IMotion::STATUS_ACCEPTED);
$disagreed = ($proposal->userStatus === IMotion::STATUS_REJECTED);

$notifications = ConsultationLog::getProposalNotification($imotion, $proposal->id, ConsultationLog::SORT_ASC);

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
                if (in_array($notification->actionType, [ConsultationLog::MOTION_NOTIFY_PROPOSAL, ConsultationLog::AMENDMENT_NOTIFY_PROPOSAL])) {
                    $data = new ProposedProcedureUserNotification($notification->data);
                    echo '<article>';
                    echo '<div>' . Tools::formatMysqlDateTime($notification->actionTime) . '</div>';
                    echo '<blockquote>' . HTMLTools::textToHtmlWithLink($data->text) . '</blockquote>';
                    echo '</article>';
                } else {
                    $data = new ProposedProcedureAgreement($notification->data);
                    echo '<article>';
                    echo '<div>' . Tools::formatMysqlDateTime($notification->actionTime) . '</div>';
                    if (in_array($notification->actionType, [ConsultationLog::MOTION_ACCEPT_PROPOSAL, ConsultationLog::AMENDMENT_ACCEPT_PROPOSAL])) {
                        echo '<span class="agreed glyphicon glyphicon-ok" aria-hidden="true"></span> ';
                        echo Yii::t('amend', 'proposal_user_agreed');
                    }
                    if (in_array($notification->actionType, [ConsultationLog::MOTION_REJECT_PROPOSAL, ConsultationLog::AMENDMENT_REJECT_PROPOSAL])) {
                        echo '<span class="agreed glyphicon glyphicon-ok" aria-hidden="true"></span> ';
                        echo Yii::t('amend', 'proposal_user_disagreed');
                    }
                    echo '<blockquote>' . HTMLTools::textToHtmlWithLink($data->comment) . '</blockquote>';
                    echo '</article>';
                }
            }
            ?>
        </div>
    </div>
    <?php
    if (!$agreed && !$disagreed) {
        ?>
        <div class="comment">
            <label for="proposalAgreeComment"><?= Yii::t('amend', 'proposal_user_comment') ?>:</label>
            <textarea class="form-control" name="comment" id="proposalAgreeComment"></textarea>
        </div>
        <?php
    }
    ?>
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
