<?

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var int $paragraphNo
 * @var string $commDelLink
 * @var bool $jsProtection
 * @var array $hiddens
 * @var MotionComment[] $comments
 */

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\db\MotionComment;
use app\models\db\MotionCommentSupporter;
use app\models\db\User;
use yii\helpers\Html;

$imadmin = User::currentUserHasPrivilege($motion->consultation, User::PRIVILEGE_SCREENING);

foreach ($comments as $comment) {
    $param    = ['motion/view', 'motionId' => $motion->id, 'commentId' => $comment->id, '#' => 'comm' . $comment->id];
    $commLink = UrlHelper::createUrl($param);
    echo '<article class="comment" id="comment' . $comment->id . '">
        <div class="date">' . Tools::formatMysqlDate($comment->dateCreation) . '</div>
        <h3>Kommentar von ' . Html::encode($comment->name);

    if ($comment->status == MotionComment::STATUS_SCREENING) {
        echo ' <em>(noch nicht freigeschaltet)</em>';
    }
    echo '</h3>';

    echo nl2br(Html::encode($comment->text));
    if (!is_null($commDelLink) && $comment->canDelete(User::getCurrentUser())) {
        echo '<div class="delLink">';
        echo Html::a('x', str_replace(rawurlencode('#commId#'), $comment->id, $commDelLink));
        echo '</div>';
    }

    if ($comment->status == MotionComment::STATUS_SCREENING && $imadmin) {
        echo Html::beginForm($commLink);
        echo '<div style="display: inline-block; width: 49%; text-align: center;">';

        echo '<button type="button" class="btn btn-success">';
        echo '<span class="glyphicon glyphicon-thumbs-up"></span> Freischalten';
        echo '</button>';

        echo '</div><div style="display: inline-block; width: 49%; text-align: center;">';

        echo '<button type="button" class="btn btn-danger">';
        echo '<span class="glyphicon glyphicon-thumbs-down"></span> Löschen';
        echo '</button>';

        echo '</div>';
        echo Html::endForm();
    }

    echo '<div class="commentBottom"><div class="kommentarlink">';
    echo Html::a("Kommentar verlinken", $commLink);
    echo '</div>';

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
					<button class="dochnicht" type="submit" name="revoke">Bewertung zurücknehmen</button>
					</span>';
        } else {
            echo '<button class="likes" type="submit" <span class="icon-thumbs-up"></span> ';
            echo $numLikes . '</button>
                    <button class="dagegen" type="submit" name="komm_dagegen">';
            echo '<span class="glyphicon glyphicon-thumbs-down"></span> ' . $numDislikes . '</button>';
        }
        echo Html::endForm();
    }
    echo '</div></div>';
}

if ($motion->consultation->getMotionPolicy()) {
    echo Html::beginForm('', 'post', ['class' => 'commentForm']);
    echo '<fieldset><legend>Kommentar schreiben</legend>';

        if ($jsProtection) {
            echo '<div class="js_protection_hint">ACHTUNG: Um diese Funktion zu nutzen, muss entweder
                JavaScript aktiviert sein, oder du musst eingeloggt sein.
            </div>';
        }
        foreach ($hiddens as $name => $value) {
            echo '<input type="hidden" name="' . Html::encode($name) . '" value="' . Html::encode($value) . '">';
        }
        echo '<input type="hidden" name="paragraphNo" value="' . $paragraphNo . '">';
        $onlyNamespaced = $motion->consultation->site->getSettings()->onlyNamespacedAccounts;
    /*
        if (!($onlyNamespaced && $motion->consultation->site->getBehaviorClass()->isLoginForced())) {
            ?>
            <div class="row">
                <?php echo $form->labelEx($kommentar_person, 'name'); ?>
                <?php echo $form->textField($kommentar_person, 'name') ?>
            </div>
            <div class="row">
                <?php echo $form->labelEx($kommentar_person, 'email'); ?>
                <?php echo $form->emailField($kommentar_person, 'email') ?>
            </div>
        <?php } ?>
        <div class="row">
            <label class="required" style="display: none;">Text</label>
            <textarea name="AntragKommentar[text]" title="Text"></textarea>
        </div>
    */
    echo '</fieldset>

    <div class="submitrow">';
        echo '<button class="btn btn-success">Kommentar abschicken</button>';
    echo '</div>';
    echo Html::endForm();
}