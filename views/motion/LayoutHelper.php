<?php

namespace app\views\motion;

use app\components\HTMLTools;
use app\components\latex\Content;
use app\components\latex\Exporter;
use app\components\latex\Layout;
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
use app\models\settings\AntragsgruenApp;
use app\models\supportTypes\ISupportType;
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
                $name .= ' (<a href="' . Html::encode($url) . '">' . \Yii::t('initiator', 'ww_profile') . '</a>)';
            }
            $admin = User::currentUserHasPrivilege($consultation, User::PRIVILEGE_SCREENING);
            if ($admin && ($supp->contactEmail != '' || $supp->contactPhone != '')) {
                $name .= '<a href="#" class="contactShow"><span class="glyphicon glyphicon-chevron-right"></span> ';
                $name .= \Yii::t('initiator', 'contact_show') . '</a>';
                $name .= '<div class="contactDetails hidden">' . \Yii::t('initiator', 'contact_only_admin') . ': ';
                if ($supp->personType == ISupporter::PERSON_ORGANIZATION) {
                    if ($supp->name != '') {
                        $name .= Html::encode($supp->name) . ', ';
                    }
                }
                if ($supp->contactEmail != '') {
                    $name .= Html::a($supp->contactEmail, 'mailto:' . $supp->contactEmail);
                    if ($supp->user && $supp->user->email == $supp->contactEmail && $supp->user->emailConfirmed) {
                        $name .= ' <span class="glyphicon glyphicon-ok-sign" style="color: gray;" ' .
                            'title="' . \Yii::t('initiator', 'email_confirmed') . '"></span>';
                    } else {
                        $name .= ' <span class="glyphicon glyphicon-question-sign" style="color: gray;" ' .
                            'title="' . \Yii::t('initiator', 'email_not_confirmed') . '"></span>';
                    }
                }
                if ($supp->contactEmail != '' && $supp->contactPhone != '') {
                    $name .= ', ';
                }
                if ($supp->contactPhone != '') {
                    $name .= \Yii::t('initiator', 'phone') . ': ' . Html::encode($supp->contactPhone);
                }
                $name .= '</div>';
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
            echo ' <span class="screeningHint">(' . \Yii::t('comment', 'not_screened_yet') . '</span>';
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

        echo HTMLTools::textToHtmlWithLink($comment->text);

        if ($screening && $imadmin) {
            echo Html::beginForm($commLink, 'post', ['class' => 'screening']);
            echo '<div style="display: inline-block; width: 49%; text-align: center;">';

            echo '<button type="submit" class="btn btn-success" name="commentScreeningAccept">';
            echo '<span class="glyphicon glyphicon-thumbs-up"></span> ' . \Yii::t('comment', 'screen_yes');
            echo '</button>';

            echo '</div><div style="display: inline-block; width: 49%; text-align: center;">';

            echo '<button type="submit" class="btn btn-danger" name="commentScreeningReject">';
            echo '<span class="glyphicon glyphicon-thumbs-down"></span> ' . \Yii::t('comment', 'screen_no');
            echo '</button>';

            echo '</div>';
            echo Html::endForm();
        }

        echo '<div class="commentBottom"><div class="commentLink">';
        echo Html::a(\Yii::t('comment', 'link_comment'), $commLink, ['class' => 'hoverElement']);
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
        echo '<legend>' . \Yii::t('comment', 'comment_write_title') . '</legend>';

        if (\Yii::$app->user->isGuest) {
            echo '<div class="jsProtectionHint">' . \Yii::t('base', 'err_js_or_login') . '</div>';
        }

        $formIdPre = 'comment_' . $sectionId . '_' . $paragraphNo;

        echo '<input type="hidden" name="comment[paragraphNo]" value="' . $paragraphNo . '">';
        echo '<input type="hidden" name="comment[sectionId]" value="' . $sectionId . '">';

        $nameIsFixed = false; // @TODO
        if (!$nameIsFixed) {
            echo '
            <div class="form-group">
                <label for="' . $formIdPre . '_name" class="control-label col-sm-3">' . \Yii::t('comment', 'name') .
                ':</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control col-sm-9" id="' . $formIdPre . '_name" ' .
                'name="comment[name]" value="' . Html::encode($form->name) . '" required autocomplete="name">
                </div>
            </div>
            <div class="form-group">
                <label for="' . $formIdPre . '_email" class="control-label col-sm-3">' . \Yii::t('comment', 'email') .
                ':</label>
                <div class="col-sm-9">
                    <input type="email" class="form-control" id="' . $formIdPre . '_email" autocomplete="email" ' .
                'name="comment[email]" value="' . Html::encode($form->email) . '"';
            if ($consultation->getSettings()->commentNeedsEmail) {
                echo ' required';
            }
            echo '>
                </div>
            </div><div class="form-group">
            <label for="' . $formIdPre . '_text" class="control-label col-sm-3">' . \Yii::t('comment', 'text') .
                ':</label>
                <div class="col-sm-9">
                    <textarea name="comment[text]"  title="Text" class="form-control" rows="5"
                    id="' . $formIdPre . '_text">' . Html::encode($form->text) . '</textarea>
                </div>
            </div>';
        } else {
            echo '<div>
            <label class="required sr-only">' . \Yii::t('comment', 'text') . '</label>
            <textarea name="comment[text]"  title="Text" class="form-control" rows="5"
                id="' . $formIdPre . '_text">' . Html::encode($form->text) . '</textarea>
            </div>';
        }
        echo '
    <div class="submitrow">
        <button class="btn btn-success" name="writeComment" type="submit">' . \Yii::t('comment', 'submit_comment') .
            '</button>
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
        $hasAgenda                = ($motion->agendaItem !== null);
        $content                  = new Content();
        $content->template        = $motion->motionType->texTemplate->texContent;
        $intro                    = explode("\n", $motion->getConsultation()->getSettings()->pdfIntroduction);
        $content->introductionBig = $intro[0];
        $content->titlePrefix     = $motion->titlePrefix;
        $content->titleLong       = $motion->getTitleWithPrefix();
        if ($hasAgenda) {
            $content->title = $motion->agendaItem->title;
        } else {
            $content->title = $motion->title;
        }
        if (count($intro) > 1) {
            array_shift($intro);
            $content->introductionSmall = implode("\n", $intro);
        }
        $initiators = [];
        foreach ($motion->getInitiators() as $init) {
            $initiators[] = $init->getNameWithResolutionDate(false);
        }
        $initiatorsStr   = implode(', ', $initiators);
        $content->author = $initiatorsStr;

        foreach ($motion->getDataTable($hasAgenda) as $key => $val) {
            $content->motionDataTable .= Exporter::encodePlainString($key) . ':   &   ';
            $content->motionDataTable .= Exporter::encodePlainString($val) . '   \\\\';
        }

        foreach ($motion->getSortedSections(true) as $section) {
            $isRight = ($section->isLayoutRight() && $motion->motionType->layoutTwoCols);
            $section->getSectionType()->printMotionTeX($isRight, $content);
        }

        $supporters = $motion->getSupporters();
        if (count($supporters) > 0) {
            $title = Exporter::encodePlainString(\Yii::t('motion', 'initiators_head'));
            $content->textMain .= '\subsection*{\AntragsgruenSection ' . $title . '}' . "\n";
            $supps = [];
            foreach ($supporters as $supp) {
                $supps[] = $supp->getNameWithOrga();
            }
            $suppStr = '<p>' . Html::encode(implode('; ', $supps)) . '</p>';
            $content->textMain .= Exporter::encodeHTMLString($suppStr);
        }

        return $content;
    }

    /**
     * @param IMotion $motion
     * @param IPolicy $policy
     * @param int $supportStatus
     */
    public static function printLikeDislikeSection(IMotion $motion, IPolicy $policy, $supportStatus)
    {
        $user = User::getCurrentUser();

        $hasLike    = ($motion->getLikeDislikeSettings() & ISupportType::LIKEDISLIKE_LIKE);
        $hasDislike = ($motion->getLikeDislikeSettings() & ISupportType::LIKEDISLIKE_DISLIKE);

        if (!$hasLike && !$hasDislike) {
            return;
        }

        $canSupport = $policy->checkCurrUser();
        foreach ($motion->getInitiators() as $supp) {
            if ($user && $supp->userId == $user->id) {
                $canSupport = false;
            }
        }

        $cantSupportMsg = $policy->getPermissionDeniedSupportMsg();

        $likes    = $motion->getLikes();
        $dislikes = $motion->getDislikes();

        $nobody = \Yii::t('structure', 'policy_nobody_supp_denied');
        if (count($likes) == 0 && count($dislikes) == 0 && $cantSupportMsg == $nobody && !$canSupport) {
            return;
        }

        echo '<section class="likes"><h2 class="green">' . \Yii::t('motion', 'likes_title') . '</h2>
    <div class="content">';

        if ($hasLike && count($likes) > 0) {
            echo '<strong>' . \Yii::t('motion', 'likes') . ':</strong><br>';
            echo '<ul>';
            foreach ($likes as $supp) {
                echo '<li>';
                if ($user && $supp->userId == $user->id) {
                    echo '<span class="label label-info">' . \Yii::t('motion', 'likes_you') . '</span> ';
                }
                echo Html::encode($supp->getNameWithOrga());
                echo '</li>';
            }
            echo '</ul>';
            echo "<br>";
        }

        if ($hasDislike && count($dislikes) > 0) {
            echo '<strong>' . \Yii::t('motion', 'dislikes') . ':</strong><br>';
            echo '<ul>';
            foreach ($dislikes as $supp) {
                echo '<li>';
                if ($user && $supp->userId == $user->id) {
                    echo '<span class="label label-info">' . \Yii::t('motion', 'dislikes_you') . '</span> ';
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
                    echo '<span class="glyphicon glyphicon-remove-sign"></span> ' . \Yii::t('motion', 'like_withdraw');
                    echo '</button>';
                    break;
                case ISupporter::ROLE_DISLIKE:
                    echo '<button type="submit" name="motionSupportRevoke" class="btn">';
                    echo '<span class="glyphicon glyphicon-remove-sign"></span> ' . \Yii::t('motion', 'like_withdraw');
                    echo '</button>';
                    break;
                default:
                    if ($hasLike) {
                        echo '<button type="submit" name="motionLike" class="btn btn-success">';
                        echo '<span class="glyphicon glyphicon-thumbs-up"></span> ' . \Yii::t('motion', 'like');
                        echo '</button>';
                    }

                    if ($hasDislike) {
                        echo '<button type="submit" name="motionDislike" class="btn btn-alert">';
                        echo '<span class="glyphicon glyphicon-thumbs-down"></span> ' . \Yii::t('motion', 'dislike');
                        echo '</button>';
                    }
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
     * @param IMotion $motion
     * @param IPolicy $policy
     * @param bool $iAmSupporting
     */
    public static function printSupportingSection(IMotion $motion, IPolicy $policy, $iAmSupporting)
    {
        $user = User::getCurrentUser();

        if (!($motion->getLikeDislikeSettings() & ISupportType::LIKEDISLIKE_SUPPORT)) {
            return;
        }

        $canSupport = $policy->checkCurrUser();
        foreach ($motion->getInitiators() as $supp) {
            if ($user && $supp->userId == $user->id) {
                $canSupport = false;
            }
        }

        $cantSupportMsg = $policy->getPermissionDeniedSupportMsg();
        $nobody         = \Yii::t('structure', 'policy_nobody_supp_denied');
        if ($cantSupportMsg == $nobody && !$canSupport) {
            return;
        }


        if ($canSupport) {
            echo Html::beginForm();

            echo '<div style="text-align: center; margin-bottom: 20px;">';
            if ($iAmSupporting) {
                echo '<button type="submit" name="motionSupportRevoke" class="btn">';
                echo '<span class="glyphicon glyphicon-remove-sign"></span> ' . \Yii::t('motion', 'like_withdraw');
                echo '</button>';
            } else {
                echo '<button type="submit" name="motionSupport" class="btn btn-success">';
                echo '<span class="glyphicon glyphicon-thumbs-up"></span> ' . \Yii::t('motion', 'support');
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
    }


    /**
     * @param Amendment[] $amendments
     * @param array $statusOverrides
     */
    public static function printAmendmentStatusSetter($amendments, $statusOverrides = [])
    {
        echo '<h2 class="green">' . \Yii::t('amend', 'merge_amend_stati') . '</h2>
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
            $opts = ['id' => 'amendmentStatus' . $amendment->id];
            echo HTMLTools::fueluxSelectbox('amendStatus[' . $amendment->id . ']', $stati, $statusPre, $opts);
            echo '</div></div>';
        }

        echo '</div>';
    }


    /**
     * @param Motion $motion
     * @return string
     */
    public static function createPdf(Motion $motion)
    {
        $cache = \Yii::$app->cache->get($motion->getPdfCacheKey());
        if ($cache && !YII_DEBUG) {
            return $cache;
        }
        $texTemplate = $motion->motionType->texTemplate;

        $layout            = new Layout();
        $layout->assetRoot = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
        //$layout->templateFile = \yii::$app->basePath . DIRECTORY_SEPARATOR .
        //    'assets' . DIRECTORY_SEPARATOR . 'motion_std.tex';
        $layout->template = $texTemplate->texLayout;
        $layout->author   = $motion->getInitiatorsStr();
        $layout->title    = $motion->getTitleWithPrefix();

        /** @var AntragsgruenApp $params */
        $params   = \yii::$app->params;
        $exporter = new Exporter($layout, $params);
        $content  = LayoutHelper::renderTeX($motion);
        $pdf      = $exporter->createPDF([$content]);
        \Yii::$app->cache->set($motion->getPdfCacheKey(), $pdf);
        return $pdf;
    }

    /**
     * @param Motion $motion
     * @return string
     */
    public static function createOdt(Motion $motion)
    {
        /** @var \app\models\settings\AntragsgruenApp $config */
        $config = \yii::$app->params;

        $template = $motion->motionType->getOdtTemplateFile();
        $doc      = new \CatoTH\HTML2OpenDocument\Text([
            'templateFile' => $template,
            'tmpPath'      => $config->tmpDir,
        ]);

        $DEBUG = (isset($_REQUEST['src']) && YII_ENV == 'dev');

        if ($DEBUG) {
            echo "<pre>";
        }

        $initiators = [];
        $supporters = [];
        foreach ($motion->motionSupporters as $supp) {
            if ($supp->role == ISupporter::ROLE_INITIATOR) {
                $initiators[] = $supp->getNameWithOrga();
            }
            if ($supp->role == ISupporter::ROLE_SUPPORTER) {
                $supporters[] = $supp->getNameWithOrga();
            }
        }
        if (count($initiators) == 1) {
            $initiatorStr = \Yii::t('export', 'InitiatorSingle');
        } else {
            $initiatorStr = \Yii::t('export', 'InitiatorMulti');
        }
        $initiatorStr .= ': ' . implode(', ', $initiators);
        $doc->addReplace('/\{\{ANTRAGSGRUEN:ITEM\}\}/siu', $motion->agendaItem ? $motion->agendaItem->title : '');
        $doc->addReplace('/\{\{ANTRAGSGRUEN:TITLE\}\}/siu', $motion->getTitleWithPrefix());
        $doc->addReplace('/\{\{ANTRAGSGRUEN:INITIATORS\}\}/siu', $initiatorStr);

        foreach ($motion->getSortedSections() as $section) {
            $section->getSectionType()->printMotionToODT($doc);
        }

        return $doc->finishAndGetDocument();
    }
}
