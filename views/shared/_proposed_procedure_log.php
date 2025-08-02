<?php

/**
 * @var Yii\web\View $this
 * @var IMotion $imotion
 */

use app\components\{HTMLTools, Tools, UrlHelper};
use app\models\db\{IAdminComment, IMotion, User};
use yii\helpers\Html;

$activities = \app\models\proposedProcedure\IActivity::getListFromIMotion($imotion);

?>
    <ol class="commentList">
        <?php
        foreach ($activities as $activity) {
            if (is_a($activity, \app\models\proposedProcedure\ActivityAdminComment::class)) {
                $comment = $activity->getAdminComment();
                $user = $comment->getMyUser();
                ?>
                <li class="activity comment" data-id="<?= $comment->id ?>">
                    <div class="header">
                        <div class="date"><?= Tools::formatMysqlDateTime($comment->dateCreation) ?></div>
                        <?php
                        if (User::isCurrentUser($user)) {
                            $url = UrlHelper::createIMotionUrl($imotion, 'del-proposal-comment');
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
                        if ($comment->status === IAdminComment::TYPE_PROPOSED_PROCEDURE) {
                            echo '<div class="overv">' . Yii::t('amend', 'proposal_comment_overview') . '</div>';
                        }
                        ?>
                        <?= HTMLTools::textToHtmlWithLink($comment->text) ?>
                    </div>
                </li>
                <?php
            }

            if (is_a($activity, \app\models\proposedProcedure\ActivityConsultationLog::class)) {
                $log = $activity->getConsultationLog();
                ?>
                <li class="activity log">
                    <div class="header">
                        <div class="date"><?= Tools::formatMysqlDateTime($log->actionTime) ?></div>
                        <div class="name"><?= Html::encode($log->user ? $log->user->name : '-') ?></div>
                    </div>
                    <div class="activity">
                        <?= $log->formatLogEntry(true) ?>
                    </div>
                </li>
                <?php
            }
        }
        ?>
    </ol>

    <textarea name="text" placeholder="<?= Html::encode(Yii::t('amend', 'proposal_comment_placeh')) ?>"
              title="<?= Html::encode(Yii::t('amend', 'proposal_comment_placeh')) ?>"
              class="form-control" rows="1"></textarea>
    <button class="btn btn-default btn-xs"><?= Yii::t('amend', 'proposal_comment_write') ?></button>
<?php
