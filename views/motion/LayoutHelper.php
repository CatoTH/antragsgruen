<?php

namespace app\views\motion;

use app\components\HTMLTools;
use app\components\latex\Content;
use app\components\latex\Exporter;
use app\components\Tools;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\IComment;
use app\models\db\IMotion;
use app\models\db\ISupporter;
use app\models\db\Motion;
use app\models\db\User;
use app\models\forms\CommentForm;
use app\models\policies\IPolicy;
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
        $screening = ($comment->status == IComment::STATUS_SCREENING);
        echo '<article class="motionComment content hoverHolder" id="comment' . $comment->id . '">
        <div class="date">' . Tools::formatMysqlDate($comment->dateCreation) . '</div>
        <h3 class="green">' . Html::encode($comment->name) . ':';

        if ($screening) {
            echo ' <span class="screeningHint">(noch nicht freigeschaltet)</span>';
        }

        if ($comment->status == IComment::STATUS_VISIBLE && $comment->canDelete(User::getCurrentUser())) {
            echo Html::beginForm($baseLink, 'post', ['class' => 'delLink hoverElement']);
            echo '<input type="hidden" name="commentId" value="' . $comment->id . '">';
            echo '<input type="hidden" name="deleteComment" value="on">';
            echo '<button class="link" type="submit">';
            echo '<span class="glyphicon glyphicon-trash"></span></button>';
            echo Html::endForm();
        }
        echo '</h3>';

        echo nl2br(Html::encode($comment->text));

        if ($screening && $imadmin) {
            echo Html::beginForm($commLink, 'post', ['class' => 'screening']);
            echo '<div style="display: inline-block; width: 49%; text-align: center;">';

            echo '<button type="submit" class="btn btn-success" name="commentScreeningAccept">';
            echo '<span class="glyphicon glyphicon-thumbs-up"></span> Freischalten';
            echo '</button>';

            echo '</div><div style="display: inline-block; width: 49%; text-align: center;">';

            echo '<button type="submit" class="btn btn-danger" name="commentScreeningReject">';
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
        $content->titleLong       = $motion->getTitleWithPrefix();
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

    /**
     * @param IMotion $motion
     * @param IPolicy $policy
     * @param int $supportStatus
     */
    public static function printSupportSection(IMotion $motion, IPolicy $policy, $supportStatus)
    {
        $user = User::getCurrentUser();

        $canSupport = $policy->checkCurrUser();
        foreach ($motion->getInitiators() as $supp) {
            if ($user && $supp->userId == $user->id) {
                $canSupport = false;
            }
        }

        $cantSupportMsg = $policy->getPermissionDeniedSupportMsg();

        $likes    = $motion->getLikes();
        $dislikes = $motion->getDislikes();

        if (count($likes) == 0 && count($dislikes) == 0 && $cantSupportMsg == '' && !$canSupport) {
            return;
        }

        echo '<section class="likes"><h2 class="green">Zustimmung</h2>
    <div class="content">';

        if (count($likes) > 0) {
            echo '<strong>Zustimmung:</strong><br>';
            echo '<ul>';
            foreach ($likes as $supp) {
                echo '<li>';
                if ($user && $supp->userId == $user->id) {
                    echo '<span class="label label-info">Du!</span> ';
                }
                echo Html::encode($supp->getNameWithOrga());
                echo '</li>';
            }
            echo '</ul>';
            echo "<br>";
        }

        if (count($dislikes) > 0) {
            echo '<strong>Ablehnung:</strong><br>';
            echo '<ul>';
            foreach ($dislikes as $supp) {
                echo '<li>';
                if ($user && $supp->userId == $user->id) {
                    echo '<span class="label label-info">Du!</span> ';
                }
                echo Html::encode($supp->getNameWithOrga());
                echo '</li>';
            }
            echo '</ul>';
            echo "<br>";
        }
        echo '</div>';

        if ($canSupport) {
            echo Html::beginForm();

            echo '<div style="text-align: center; margin-bottom: 20px;">';
            switch ($supportStatus) {
                case ISupporter::ROLE_INITIATOR:
                    break;
                case ISupporter::ROLE_LIKE:
                    echo '<button type="submit" name="motionSupportRevoke" class="btn">';
                    echo '<span class="glyphicon glyphicon-remove-sign"></span> Doch nicht';
                    echo '</button>';
                    break;
                case ISupporter::ROLE_DISLIKE:
                    echo '<button type="submit" name="motionSupportRevoke" class="btn">';
                    echo '<span class="glyphicon glyphicon-remove-sign"></span> Doch nicht';
                    echo '</button>';
                    break;
                default:
                    echo '<button type="submit" name="motionLike" class="btn btn-success">';
                    echo '<span class="glyphicon glyphicon-thumbs-up"></span> Zustimmung';
                    echo '</button>';

                    echo '<button type="submit" name="motionDislike" class="btn btn-alert">';
                    echo '<span class="glyphicon glyphicon-thumbs-down"></span> Ablehnung';
                    echo '</button>';
            }
            echo '</div>';
            echo Html::endForm();
        } else {
            if ($cantSupportMsg != '') {
                echo '<div class="alert alert-danger" role="alert">
                <span class="icon glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <span class="sr-only">Error:</span>
                ' . Html::encode($cantSupportMsg) . '
            </div>';
            }
        }
        echo '</section>';
    }


    /**
     * @param Amendment[] $amendments
     * @param array $statusOverrides
     */
    public static function printAmendmentStatusSetter($amendments, $statusOverrides = [])
    {
        echo '<h2 class="green">' . 'Status der Änderungsasnträge' . '</h2>
    <div class="content form-horizontal">';

        foreach ($amendments as $amendment) {
            $changeset = (isset($changesets[$amendment->id]) ? $changesets[$amendment->id] : []);
            $data      = 'data-old-status="' . $amendment->status . '"';
            $data .= ' data-amendment-id="' . $amendment->id . '"';
            $data .= ' data-changesets="' . Html::encode(json_encode($changeset)) . '"';
            echo '<div class="form-group amendmentStatus" ' . $data . '>
    <label for="amendmentStatus' . $amendment->id . '" class="col-sm-3 control-label">';
            echo Html::encode($amendment->getShortTitle()) . ':<br><span class="amendSubtitle">';
            echo Html::encode($amendment->getInitiatorsStr());
            echo '</span></label>
    <div class="col-md-9">';
            $statiAll                  = $amendment->getStati();
            $stati                     = [
                Amendment::STATUS_ACCEPTED          => $statiAll[Amendment::STATUS_ACCEPTED],
                Amendment::STATUS_REJECTED          => $statiAll[Amendment::STATUS_REJECTED],
                Amendment::STATUS_MODIFIED_ACCEPTED => $statiAll[Amendment::STATUS_MODIFIED_ACCEPTED],
            ];
            $stati[$amendment->status] = 'unverändert: ' . $statiAll[$amendment->status];
            if (isset($statusOverrides[$amendment->id])) {
                $statusPre = $statusOverrides[$amendment->id];
            } else {
                $statusPre = $amendment->status;
            }
            $opts = ['id' => 'amendmentSttus' . $amendment->id];
            echo HTMLTools::fueluxSelectbox('amendStatus[' . $amendment->id . ']', $stati, $statusPre, $opts);
            echo '</div></div>';
        }

        echo '</div>';
    }
}
