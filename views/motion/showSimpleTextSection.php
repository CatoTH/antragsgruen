<?php
/**
 * @var \app\models\db\MotionSection $section
 * @var int[] $openedComments
 * @var null|CommentForm $commentForm
 */

use app\components\UrlHelper;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\MotionComment;
use app\models\db\User;
use app\models\forms\CommentForm;
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

$hasLineNumbers = $section->consultationSetting->lineNumbers;
$paragraphs     = $section->getTextParagraphObjects($hasLineNumbers, true, true);
$screenAdmin    = User::currentUserHasPrivilege($section->motion->consultation, User::PRIVILEGE_SCREENING);
$classes        = ['paragraph'];
if ($hasLineNumbers) {
    $classes[] = 'lineNumbers';
    $lineNo    = $section->getFirstLineNumber();
}

foreach ($paragraphs as $paragraphNo => $paragraph) {
    $parClasses = $classes;
    if (mb_stripos($paragraph->lines[0], '<ul>') === 0) {
        $parClasses[] = 'list';
    } elseif (mb_stripos($paragraph->lines[0], '<ol>') === 0) {
        $parClasses[] = 'list';
    } elseif (mb_stripos($paragraph->lines[0], '<blockquote>') === 0) {
        $parClasses[] = 'blockquote';
    }
    if (in_array($paragraphNo, $openedComments)) {
        $parClasses[] = 'commentsOpened';
    }
    $id = 'section_' . $section->sectionId . '_' . $paragraphNo;
    echo '<section class="' . implode(' ', $parClasses) . '" id="' . $id . '">';


    echo '<ul class="bookmarks">';
    if ($section->consultationSetting->hasComments == ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS) {
        $mayOpen = $section->motion->motionType->getCommentPolicy()->checkCurrUser(true, true);
        $numComments = $paragraph->getVisibleComments($screenAdmin);
        if (count($numComments) > 0 || $mayOpen) {
            echo '<li class="comment">';
            $str = '<span class="glyphicon glyphicon-comment"></span>';
            $str .= '<span class="count" data-count="' . count($numComments) . '"></span>';
            $zero = '';
            if (count($numComments) == 0) {
                $zero .= ' zero';
            }
            echo Html::a($str, '#', ['class' => 'shower' . $zero]);
            echo Html::a($str, '#', ['class' => 'hider' . $zero]);
            echo '</li>';
        }
    }

    foreach ($paragraph->amendmentSections as $amendmentSection) {
        $amendment = $amendmentSection->amendmentSection->amendment;
        $amLink    = UrlHelper::createAmendmentUrl($amendment);
        $firstline = $amendmentSection->firstAffectedLine;
        echo '<li class="amendment amendment' . $amendment->id . '" data-first-line="' . $firstline . '">';
        echo '<a data-id="' . $amendment->id . '" href="' . Html::encode($amLink) . '">';
        echo Html::encode($amendment->titlePrefix) . "</a></li>\n";
    }

    echo '</ul>';

    echo '<div class="text textOrig">';
    $linesArr = [];
    foreach ($paragraph->lines as $line) {
        if ($section->consultationSetting->lineNumbers) {
            /** @var int $lineNo */
            $lineNoStr = '<span class="lineNumber" data-line-number="' . $lineNo++ . '"></span>';
            $line      = str_replace('###LINENUMBER###', $lineNoStr, $line);
        }
        $line       = str_replace('###FORCELINEBREAK###', '', $line);
        $linesArr[] = $line;
    }
    echo implode('<br>', $linesArr);
    echo '</div>';

    foreach ($paragraph->amendmentSections as $amendmentSection) {
        $amendment = $amendmentSection->amendmentSection->amendment;
        echo '<div class="text textAmendment hidden amendment' . $amendment->id . '">';
        echo '<div class="preamble"><div>';
        echo '<h3>Änderungsantrag ' . Html::encode($amendment->titlePrefix) . '</h3>';
        echo ', gestellt von: ' . Html::encode($amendment->getInitiatorsStr());
        $amParas = $amendment->getChangedParagraphs(true);
        if (count($amParas) > 1) {
            echo '<div class="moreAffected">';
            echo str_replace('%num%', count($amParas), 'Bezieht sich auf insgesamt %num% Absätze');
            echo '</div>';
        }
        echo '</div></div>';
        echo $amendmentSection->strDiff;
        echo '</div>';
    }

    if ($section->consultationSetting->hasComments == ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS) {
        if (count($paragraph->comments) > 0 || $section->motion->motionType->getCommentPolicy()) {
            echo '<section class="commentHolder">';
            $motion = $section->motion;
            $form   = $commentForm;

            if (in_array($paragraphNo, $openedComments)) {
                $screening = \Yii::$app->session->getFlash('screening', null, true);
                if ($screening) {
                    echo '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">Success:</span>
                ' . Html::encode($screening) . '
            </div>';
                }
            }

            if ($form === null || $form->paragraphNo != $paragraphNo || $form->sectionId != $section->sectionId) {
                $form              = new \app\models\forms\CommentForm();
                $form->paragraphNo = $paragraphNo;
                $form->sectionId   = $section->sectionId;
            }

            $screeningQueue = 0;
            foreach ($paragraph->comments as $comment) {
                if ($comment->status == MotionComment::STATUS_SCREENING) {
                    $screeningQueue++;
                }
            }
            if ($screeningQueue > 0) {
                echo '<div class="commentScreeningQueue">';
                if ($screeningQueue == 1) {
                    echo '1 Kommentar wartet auf Freischaltung';
                } else {
                    echo str_replace('%NUM%', $screeningQueue, '%NUM% Kommentare warten auf Freischaltung');
                }
                echo '</div>';
            }
            $baseLink = UrlHelper::createMotionUrl($motion);
            foreach ($paragraph->getVisibleComments($screenAdmin) as $comment) {
                $commLink = UrlHelper::createMotionCommentUrl($comment);
                LayoutHelper::showComment($comment, $screenAdmin, $baseLink, $commLink);
            }

            if ($section->motion->motionType->getCommentPolicy()->checkCurrUser()) {
                LayoutHelper::showCommentForm($form, $motion->consultation, $section->sectionId, $paragraphNo);
            } elseif ($section->motion->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
                echo '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in"></span>
        Logge dich ein, um kommentieren zu können.
        </div>';
            }
            echo '</section>';
        }
    }


    echo '</section>';
}
