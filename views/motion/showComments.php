<?php

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var int $sectionId
 * @var int $paragraphNo
 * @var MotionComment[] $comments
 * @var null|\app\models\forms\CommentForm $form
 */

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\MotionCommentSupporter;
use app\models\db\User;
use yii\helpers\Html;

$imadmin = User::currentUserHasPrivilege($motion->consultation, User::PRIVILEGE_SCREENING);
if ($form === null || $form->paragraphNo != $paragraphNo || $form->sectionId != $sectionId) {
    $form              = new \app\models\forms\CommentForm();
    $form->paragraphNo = $paragraphNo;
    $form->sectionId   = $sectionId;
}

foreach ($comments as $comment) {
    $param    = ['motion/view', 'motionId' => $motion->id, 'commentId' => $comment->id, '#' => 'comm' . $comment->id];
    $commLink = UrlHelper::createUrl($param);
    echo '<article class="motionComment content hoverHolder" id="comment' . $comment->id . '">
        <div class="date">' . Tools::formatMysqlDate($comment->dateCreation) . '</div>
        <h3>Kommentar von ' . Html::encode($comment->name);

    if ($comment->status == MotionComment::STATUS_SCREENING) {
        echo ' <em>(noch nicht freigeschaltet)</em>';
    }
    echo '</h3>';

    echo nl2br(Html::encode($comment->text));
    if ($comment->canDelete(User::getCurrentUser())) {
        echo Html::beginForm('', 'post', ['class' => 'delLink hoverElement']);
        echo '<input type="hidden" name="commentId" value="' . $comment->id . '">';
        echo '<button class="link" type="submit" name="deleteComment">';
        echo '<span class="glyphicon glyphicon-trash"></span></button>';
        echo Html::endForm();
    }

    if ($comment->status == MotionComment::STATUS_SCREENING && $imadmin) {
        echo Html::beginForm($commLink);
        echo '<div style="display: inline-block; width: 49%; text-align: center;">';

        echo '<button type="button" class="btn btn-success" name="commentScreeningAccept">';
        echo '<span class="glyphicon glyphicon-thumbs-up"></span> Freischalten';
        echo '</button>';

        echo '</div><div style="display: inline-block; width: 49%; text-align: center;">';

        echo '<button type="button" class="btn btn-danger" name="commentScreeningReject">';
        echo '<span class="glyphicon glyphicon-thumbs-down"></span> Löschen';
        echo '</button>';

        echo '</div>';
        echo Html::endForm();
    }

    echo '<div class="commentBottom"><div class="commentLink">';
    echo Html::a("Kommentar verlinken", $commLink, ['class' => 'hoverElement']);
    echo '</div>';

    /*
    if ($motion->consultation->getSettings()->commentsSupportable) {
        echo Html::beginForm($commLink, 'post', ['class' => 'commentSupporterHolder']);

        $mySupports = MotionCommentSupporter::mySupport($motion);

        $numLikes = $numDislikes = 0;
        foreach ($comment->supporters as $supp) {
            if ($supp->likes) {
                $numLikes++;
            } else {
                $numDislikes++;
            }
        }
        if ($mySupports !== null) {
            echo '<span class="likes"><span class="glyphicon glyphicon-thumbs-up"></span> ';
            echo $numLikes . '</span>';
            echo '<span class="dislikes"><span class="glyphicon glyphicon-thumbs-down"></span> ';
            echo $numDislikes . '</span>';
            echo '<span class="mine"><span class="currently">';
            if ($mySupports->likes) {
                echo '<span class="glyphicon glyphicon-thumbs-up"></span> ';
                echo 'Du hast diesen Kommentar positiv bewertet';
            } else {
                echo '<span class="icon-thumbs-down"></span> Du hast diesen Kommentar negativ bewertet';
            }
            echo '</span>
					<button class="revoke" type="submit" name="commentUndoLike">Bewertung zurücknehmen</button>
				</span>';
        } else {
            echo '<button class="likes" type="submit" name="commentLike">';
            echo '<span class="glyphicon glyphicon-thumbs-up"></span> ' . $numLikes . '</button>
                    <button class="dislikes" type="submit" name="commentDislike">';
            echo '<span class="glyphicon glyphicon-thumbs-down"></span> ' . $numDislikes . '</button>';
        }
        echo Html::endForm();
    }
    */
    echo '</div></article>';
}

if ($motion->consultation->getMotionPolicy()) {
    echo Html::beginForm('', 'post', ['class' => 'commentForm form-horizontal row']);
    echo '<fieldset class="col-md-8 col-md-offset-2">';
    echo '<label>Kommentar schreiben</label>';

    if (\Yii::$app->user->isGuest) {
        echo '<div class="jsProtectionHint">ACHTUNG: Um diese Funktion zu nutzen, muss entweder
                JavaScript aktiviert sein, oder du musst eingeloggt sein.
            </div>';
    }

    $formIdPre = 'comment_' . $sectionId . '_' . $paragraphNo;

    echo '<input type="hidden" name="comment[paragraphNo]" value="' . $paragraphNo . '">';
    echo '<input type="hidden" name="comment[sectionId]" value="' . $sectionId . '">';
    $onlyNamespaced = $motion->consultation->site->getSettings()->onlyNamespacedAccounts;
    if (!($onlyNamespaced && $motion->consultation->site->getBehaviorClass()->isLoginForced())) {
        echo '
            <div class="form-group">
                <label for="' . $formIdPre . '_name" class="control-label col-sm-3">Name:</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control col-sm-9" id="' . $formIdPre . '_name"
                        name="comment[name]" value="' . Html::encode($form->name) . '" required>
                </div>
            </div>
            <div class="form-group">
                <label for="' . $formIdPre . '_email" class="control-label col-sm-3">E-Mail:</label>
                <div class="col-sm-9">
                    <input type="email" class="form-control" id="' . $formIdPre . '_email"
                    name="comment[email]" value="' . Html::encode($form->email) . '"';
        if ($motion->consultation->getSettings()->commentNeedsEmail) {
            echo ' required';
        }
        echo '>
                </div>
            </div><div class="form-group">
            <label for="' . $formIdPre . '_text" class="control-label col-sm-3">Text:</label>
                <div class="col-sm-9">
                    <textarea name="comment[text]"  title="Text" class="form-control" rows="5"
                    id="' . $formIdPre . '_text">' . Html::encode($form->text) . '</textarea>
                </div>
            </div>';
    } else {
        echo '<div>
            <label class="required sr-only">Text</label>
            <textarea name="comment[text]"  title="Text" class="form-control" rows="5"
                id="' . $formIdPre . '_text">' . Html::encode($form->text) . '</textarea>
            </div>';
    }
    echo '
    <div class="submitrow"><button class="btn btn-success" name="writeComment">Kommentar abschicken</button></div>
    </fieldset>';

    echo Html::endForm();
}
