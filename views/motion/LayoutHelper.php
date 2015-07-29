<?php

namespace app\views\motion;

use app\components\latex\Content;
use app\components\latex\Exporter;
use app\components\Tools;
use app\models\db\Consultation;
use app\models\db\IComment;
use app\models\db\ISupporter;
use app\models\db\Motion;
use app\models\db\User;
use app\models\forms\CommentForm;
use yii\helpers\Html;

class LayoutHelper
{
    /**
     * @param ISupporter[] $initiators
     * @param Consultation $consultation
     * @return string
     */
    public static function formatInitiators($initiators, Consultation $consultation)
    {
        $inits = [];
        foreach ($initiators as $supp) {
            $name = $supp->getNameWithResolutionDate(true);
            if ($supp->user && $supp->user->isWurzelwerkUser()) {
                $url = 'https://wurzelwerk.gruene.de/web/' . $supp->user->getWurzelwerkName();
                $name .= ' (<a href="' . Html::encode($url) . '">Wurzelwerk-Profil</a>)';
            }
            $admin = User::currentUserHasPrivilege($consultation, User::PRIVILEGE_SCREENING);
            if ($admin && ($supp->contactEmail != '' || $supp->contactPhone != '')) {
                $name .= '<br><small>Kontaktdaten, nur als Admin sichtbar: ';
                if ($supp->contactEmail != '') {
                    $name .= Html::a($supp->contactEmail, 'mailto:' . $supp->contactEmail);
                    if ($supp->user && $supp->user->email == $supp->contactEmail && $supp->user->emailConfirmed) {
                        $name .= ' <span class="glyphicon glyphicon-ok-sign" style="color: gray;" ' .
                            'title="' . 'E-Mail-Adresse bestätigt' . '"></span>';
                    } else {
                        $name .= ' <span class="glyphicon glyphicon-question-sign" style="color: gray;" ' .
                            'title="' . 'E-Mail-Adresse nicht bestätigt' . '"></span>';
                    }
                }
                if ($supp->contactEmail != '' && $supp->contactPhone != '') {
                    $name .= ', ';
                }
                if ($supp->contactPhone != '') {
                    $name .= 'Telefon: ' . Html::encode($supp->contactPhone);
                }
                $name .= "</small>";
            }
            $inits[] = $name;
        }
        return implode(', ', $inits);
    }

    /**
     * @param IComment $comment
     * @param bool $imadmin
     * @param string $baseLink
     * @param string $commLink
     */
    public static function showComment(IComment $comment, $imadmin, $baseLink, $commLink)
    {
        echo '<article class="motionComment content hoverHolder" id="comment' . $comment->id . '">
        <div class="date">' . Tools::formatMysqlDate($comment->dateCreation) . '</div>
        <h3 class="green">Kommentar von ' . Html::encode($comment->name);

        if ($comment->status == IComment::STATUS_SCREENING) {
            echo ' <em>(noch nicht freigeschaltet)</em>';
        }
        echo '</h3>';

        echo nl2br(Html::encode($comment->text));
        if ($comment->canDelete(User::getCurrentUser())) {
            echo Html::beginForm($baseLink, 'post', ['class' => 'delLink hoverElement']);
            echo '<input type="hidden" name="commentId" value="' . $comment->id . '">';
            echo '<input type="hidden" name="deleteComment" value="on">';
            echo '<button class="link" type="submit">';
            echo '<span class="glyphicon glyphicon-trash"></span></button>';
            echo Html::endForm();
        }

        if ($comment->status == IComment::STATUS_SCREENING && $imadmin) {
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

    /**
     * @param CommentForm $form
     * @param Consultation $consultation
     * @param int $sectionId
     * @param int $paragraphNo
     */
    public static function showCommentForm(CommentForm $form, Consultation $consultation, $sectionId, $paragraphNo)
    {
        echo Html::beginForm('', 'post', ['class' => 'commentForm form-horizontal row']);
        echo '<fieldset class="col-md-8 col-md-offset-2">';
        echo '<legend>Kommentar schreiben</legend>';

        if (\Yii::$app->user->isGuest) {
            echo '<div class="jsProtectionHint">ACHTUNG: Um diese Funktion zu nutzen, muss entweder
                JavaScript aktiviert sein, oder du musst eingeloggt sein.
            </div>';
        }

        $formIdPre = 'comment_' . $sectionId . '_' . $paragraphNo;

        echo '<input type="hidden" name="comment[paragraphNo]" value="' . $paragraphNo . '">';
        echo '<input type="hidden" name="comment[sectionId]" value="' . $sectionId . '">';

        $nameIsFixed = false; // @TODO
        if (!$nameIsFixed) {
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
            if ($consultation->getSettings()->commentNeedsEmail) {
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
    <div class="submitrow">
        <button class="btn btn-success" name="writeComment" type="submit">Kommentar abschicken</button>
    </div>
    </fieldset>';

        echo Html::endForm();
    }

    /**
     * @param Motion $motion
     * @return Content
     */
    public static function renderTeX(Motion $motion)
    {
        $content                  = new Content();
        $content->template        = $motion->motionType->texTemplate->texContent;
        $intro                    = explode("\n", $motion->consultation->getSettings()->pdfIntroduction);
        $content->introductionBig = $intro[0];
        $content->title           = $motion->title;
        $content->titlePrefix     = $motion->titlePrefix;
        $content->titleLong       = $motion->title;
        if (count($intro) > 1) {
            array_shift($intro);
            $content->introductionSmall = implode("\n", $intro);
        } else {
            $content->introductionSmall = '';
        }
        $initiators = [];
        foreach ($motion->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }
        $initiatorsStr   = implode(', ', $initiators);
        $content->author = $initiatorsStr;

        $content->motionDataTable = '';
        foreach ($motion->getDataTable() as $key => $val) {
            $content->motionDataTable .= Exporter::encodePlainString($key) . ':   &   ';
            $content->motionDataTable .= Exporter::encodePlainString($val) . '   \\\\';
        }

        $content->text = '';
        foreach ($motion->getSortedSections(true) as $section) {
            $content->text .= $section->getSectionType()->getMotionTeX();
        }

        $supporters = $motion->getSupporters();
        if (count($supporters) > 0) {
            $title = Exporter::encodePlainString('UnterstützerInnen');
            $content->text .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $supps = [];
            foreach ($supporters as $supp) {
                $supps[] = $supp->getNameWithOrga();
            }
            $suppStr = '<p>' . Html::encode(implode('; ', $supps)) . '</p>';
            $content->text .= Exporter::encodeHTMLString($suppStr);
        }

        return $content;
    }
}
