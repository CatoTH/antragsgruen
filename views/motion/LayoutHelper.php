<?php

namespace app\views\motion;

use app\components\HTMLTools;
use app\components\latex\Content;
use app\components\latex\Exporter;
use app\components\latex\Layout;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\IMotion;
use app\models\db\ISupporter;
use app\models\db\Motion;
use app\models\db\User;
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\settings\AntragsgruenApp;
use app\models\supportTypes\ISupportType;
use app\views\pdfLayouts\IPDFLayout;
use setasign\Fpdi\TcpdfFpdi;
use yii\helpers\Html;

class LayoutHelper
{
    /**
     * @param ISupporter[] $initiators
     * @param Consultation $consultation
     * @param bool $expanded
     * @param bool $adminMode
     * @return string
     */
    public static function formatInitiators($initiators, $consultation, $expanded = false, $adminMode = false)
    {
        $inits = [];
        foreach ($initiators as $supp) {
            $name = $supp->getNameWithResolutionDate(true);
            if ($supp->user && $supp->user->isWurzelwerkUser()) {
                $url = 'https://wurzelwerk.gruene.de/web/' . $supp->user->getWurzelwerkName();
                if ($adminMode) {
                    $name .= ' (<a href="' . Html::encode($url) . '">WW</a>)';
                } else {
                    $name .= ' (<a href="' . Html::encode($url) . '">' . \Yii::t('initiator', 'ww_profile') . '</a>)';
                }
            }
            $admin = User::havePrivilege($consultation, [User::PRIVILEGE_SCREENING, User::PRIVILEGE_CHANGE_PROPOSALS]);
            if ($admin && ($supp->contactEmail != '' || $supp->contactPhone != '')) {
                if (!$expanded) {
                    $name .= '<a href="#" class="contactShow"><span class="glyphicon glyphicon-chevron-right"></span> ';
                    $name .= \Yii::t('initiator', 'contact_show') . '</a>';
                }

                $name .= '<div class="contactDetails' . ($expanded ? '' : ' hidden') . '">';
                if (!$adminMode) {
                    $name .= \Yii::t('initiator', 'visibilityAdmins') . ': ';
                }
                if ($supp->personType == ISupporter::PERSON_ORGANIZATION) {
                    if ($supp->name != '') {
                        $name .= Html::encode($supp->name) . ', ';
                    }
                }
                if ($supp->contactEmail != '') {
                    $name .= Html::a(Html::encode($supp->contactEmail), 'mailto:' . $supp->contactEmail);
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
     * @param Motion $motion
     * @return Content
     * @throws \app\models\exceptions\Internal
     */
    public static function renderTeX(Motion $motion)
    {
        $hasAgenda                = ($motion->agendaItem !== null);
        $content                  = new Content();
        $content->template        = $motion->getMyMotionType()->texTemplate->texContent;
        $intro                    = explode("\n", $motion->getMyMotionType()->getSettingsObj()->pdfIntroduction);
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
            $isRight = ($section->isLayoutRight() && $motion->motionType->getSettingsObj()->layoutTwoCols);
            $section->getSectionType()->printMotionTeX($isRight, $content, $motion->getMyConsultation());
        }

        $supporters = $motion->getSupporters();
        if (count($supporters) > 0) {
            $title = Exporter::encodePlainString(\Yii::t('motion', 'supporters_heading'));
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
     * @param TcpdfFpdi $pdf
     * @param IPDFLayout $pdfLayout
     * @param Motion $motion
     * @throws \app\models\exceptions\Internal
     */
    public static function printToPDF(TcpdfFpdi $pdf, IPDFLayout $pdfLayout, Motion $motion)
    {
        error_reporting(error_reporting() & ~E_DEPRECATED); // TCPDF ./. PHP 7.2

        $pdf->startPageGroup();
        $pdf->AddPage();

        $pdfLayout->printMotionHeader($motion);

        // PDFs should be attached at the end, to prevent collission with other parts of the motion text; see #242
        $pdfAttachments = [];
        foreach ($motion->getSortedSections(true) as $section) {
            if ($section->getSettings()->type == ISectionType::TYPE_PDF) {
                $pdfAttachments[] = $section;
            } else {
                $section->getSectionType()->printMotionToPDF($pdfLayout, $pdf);
            }
        }
        foreach ($pdfAttachments as $section) {
            $section->getSectionType()->printMotionToPDF($pdfLayout, $pdf);
        }
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
                if ($cantSupportMsg == \Yii::t('structure', 'policy_logged_supp_denied')) {
                    $icon = '<span class="icon glyphicon glyphicon-log-in" aria-hidden="true"></span>&nbsp; ';
                } else {
                    $icon = '<span class="icon glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>';
                }
                echo '<div class="alert alert-info" role="alert">' .
                    $icon . '<span class="sr-only">Error:</span>' . Html::encode($cantSupportMsg) . '
                    </div>';
            }
        }
        echo '</div>';
        echo '</section>';
    }

    /**
     * @param IMotion $motion
     * @param IPolicy $policy
     * @param ISupportType $supportType
     * @param bool $iAmSupporting
     */
    public static function printSupportingSection($motion, $policy, $supportType, $iAmSupporting)
    {
        $user = User::getCurrentUser();

        if (!($motion->getLikeDislikeSettings() & ISupportType::LIKEDISLIKE_SUPPORT)) {
            return;
        }

        $canSupport = $policy->checkCurrUser();
        foreach ($motion->getInitiators() as $supp) {
            if ($user && $supp->userId == $user->id) {
                return;
            }
        }

        $cantSupportMsg = $policy->getPermissionDeniedSupportMsg();
        $nobody         = \Yii::t('structure', 'policy_nobody_supp_denied');
        if ($cantSupportMsg == $nobody && !$canSupport) {
            return;
        }
        if (!$motion->isSupportingPossibleAtThisStatus()) {
            return;
        }

        if ($canSupport) {
            echo Html::beginForm('', 'post', ['class' => 'motionSupportForm']);

            if ($iAmSupporting) {
                echo '<div style="text-align: center; margin-bottom: 20px;">';
                echo '<button type="submit" name="motionSupportRevoke" class="btn">';
                echo '<span class="glyphicon glyphicon-remove-sign"></span> ' . \Yii::t('motion', 'like_withdraw');
                echo '</button>';
                echo '</div>';
            } else {
                echo \Yii::$app->controller->renderPartial('@app/views/motion/_support_block', [
                    'user'        => $user,
                    'supportType' => $supportType,
                ]);
            }
            echo Html::endForm();
        } else {
            if ($cantSupportMsg != '') {
                if ($cantSupportMsg == \Yii::t('structure', 'policy_logged_supp_denied')) {
                    $icon = '<span class="icon glyphicon glyphicon-log-in" aria-hidden="true"></span>&nbsp; ';
                } else {
                    $icon = '<span class="icon glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>';
                }
                echo '<div class="alert alert-info" role="alert">' . $icon .
                    '<span class="sr-only">Error:</span>' . Html::encode($cantSupportMsg) . '
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
        echo '<h2 class="green">' . \Yii::t('amend', 'merge_amend_statuses') . '</h2>
    <div class="content form-horizontal">';

        foreach ($amendments as $amendment) {
            //$changeset = (isset($changesets[$amendment->id]) ? $changesets[$amendment->id] : []);
            $changeset = [];
            $data      = 'data-old-status="' . $amendment->status . '"';
            $data .= ' data-amendment-id="' . $amendment->id . '"';
            $data .= ' data-changesets="' . Html::encode(json_encode($changeset)) . '"';
            echo '<div class="form-group amendmentStatus" ' . $data . '>
    <label for="amendmentStatus' . $amendment->id . '" class="col-sm-3 control-label">';
            echo Html::encode($amendment->getShortTitle()) . ':<br><span class="amendSubtitle">';
            echo Html::encode($amendment->getInitiatorsStr());
            echo '</span></label>
    <div class="col-md-9">';
            $statusesAll                  = $amendment->getStatusNames();
            $statuses                     = [
                Amendment::STATUS_PROCESSED         => $statusesAll[Amendment::STATUS_PROCESSED],
                Amendment::STATUS_ACCEPTED          => $statusesAll[Amendment::STATUS_ACCEPTED],
                Amendment::STATUS_REJECTED          => $statusesAll[Amendment::STATUS_REJECTED],
                Amendment::STATUS_MODIFIED_ACCEPTED => $statusesAll[Amendment::STATUS_MODIFIED_ACCEPTED],
            ];
            $statuses[$amendment->status] = \Yii::t('amend', 'merge_status_unchanged') . ': ' .
                $statusesAll[$amendment->status];
            if (isset($statusOverrides[$amendment->id])) {
                $statusPre = $statusOverrides[$amendment->id];
            } else {
                $statusPre = Amendment::STATUS_PROCESSED;
            }
            $opts = ['id' => 'amendmentStatus' . $amendment->id];
            echo HTMLTools::fueluxSelectbox('amendStatus[' . $amendment->id . ']', $statuses, $statusPre, $opts);
            echo '</div></div>';
        }

        echo '</div>';
    }


    /**
     * @param Motion $motion
     * @return string
     * @throws \app\models\exceptions\Internal
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
     * @param string $url
     * @param string $title
     * @return string
     */
    public static function getShareButtons($url, $title)
    {
        $twitter  = Html::encode(
            'https://twitter.com/intent/tweet?text=' . urlencode($title) . '&url=' . urlencode($url)
        );
        $facebook = Html::encode(
            'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($url)
        );
        return '<div class="share_buttons"><ul>
              <li class="twitter"><a href="' . $twitter . '" title="Bei Twitter teilen">
                 <span class="icon fontello-twitter"></span> <span class="share_text">tweet</span>
              </a></li>
              <li class="facebook"><a href="' . $facebook . '" title="Bei Facebook teilen">
                  <span class="icon fontello-facebook"></span> <span class="share_text">teilen</span>
              </a></li>
            </ul></div>';
    }
}
