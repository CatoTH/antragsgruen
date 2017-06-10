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

$motion         = $section->getMotion();
$hasLineNumbers = $section->getSettings()->lineNumbers;
$paragraphs     = $section->getTextParagraphObjects($hasLineNumbers, true, true);
$screenAdmin    = User::currentUserHasPrivilege($section->getConsultation(), User::PRIVILEGE_SCREENING);
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
    echo '<div class="' . implode(' ', $parClasses) . '" id="' . $id . '">';


    echo '<ul class="bookmarks">';
    if ($section->getSettings()->hasComments == ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS) {
        $mayOpen     = $section->getMotion()->motionType->getCommentPolicy()->checkCurrUser(true, true);
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
        $amendment = \app\models\db\Consultation::getCurrent()->getAmendment($amendmentSection->amendmentId);
        $amLink    = UrlHelper::createAmendmentUrl($amendment);
        $firstline = $amendmentSection->firstAffectedLine;
        echo '<li class="amendment amendment' . $amendment->id . '" data-first-line="' . $firstline . '">';
        echo '<a data-id="' . $amendment->id . '" href="' . Html::encode($amLink) . '">';
        echo Html::encode($amendment->titlePrefix) . "</a></li>\n";
    }

    echo '</ul>';

    echo '<div class="text textOrig';
    if ($section->getSettings()->fixedWidth) {
        echo ' fixedWidthFont';
    }
    echo '">';
    if ($section->getSettings()->fixedWidth || $hasLineNumbers) {
        foreach ($paragraph->lines as $i => $line) {
            if ($section->getSettings()->lineNumbers) {
                /** @var int $lineNo */
                $lineNoStr = '<span class="lineNumber" data-line-number="' . $lineNo++ . '"></span>';
                $line      = str_replace('###LINENUMBER###', $lineNoStr, $line);
            }
            $line   = str_replace('<br>', '', $line);
            $first3 = substr($line, 0, 3);
            if ($i > 0 && !in_array($first3, ['<ol', '<ul', '<p>', '<di'])) {
                echo '<br>';
            }
            echo $line;
        }
    } else {
        echo $paragraph->origStr;
    }
    echo '</div>';

    foreach ($paragraph->amendmentSections as $amendmentSection) {
        $amendment = \app\models\db\Consultation::getCurrent()->getAmendment($amendmentSection->amendmentId);
        echo '<div class="text textAmendment hidden amendment' . $amendment->id;
        if ($section->getSettings()->fixedWidth) {
            echo ' fixedWidthFont';
        }
        echo '">';
        echo '<div class="preamble"><div>';
        echo '<h3>' . \Yii::t('amend', 'amendment') . ' ' . Html::encode($amendment->titlePrefix) . '</h3>';
        echo ', ' . \Yii::t('amend', 'initiated_by') . ': ' . Html::encode($amendment->getInitiatorsStr());
        $amParas = $amendment->getChangedParagraphs($motion->getActiveSections(), true);
        if (count($amParas) > 1) {
            echo '<div class="moreAffected">';
            echo str_replace('%num%', count($amParas), \Yii::t('amend', 'affects_x_paragraphs'));
            echo '</div>';
        }
        echo '</div></div>';
        echo str_replace('###LINENUMBER###', '', $amendmentSection->strDiff);
        echo '</div>';

        // Seems to be necessary to limit memory consumption
        // Problem can be seen e.g. at https://bdk.antragsgruen.de/39/motion/144
        unset($amParas);
        unset($amendmentSection->amendmentSection);
        unset($amendmentSection);
        unset($amendment);
        gc_collect_cycles();
    }

    if ($section->getSettings()->hasComments == ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS) {
        if (count($paragraph->comments) > 0 || $section->getMotion()->motionType->getCommentPolicy()) {
            echo '<section class="commentHolder">';
            $motion = $section->getMotion();
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
                $user              = User::getCurrentUser();
                if ($user) {
                    $form->name  = $user->name;
                    $form->email = $user->email;
                }
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
                    echo \Yii::t('amend', 'comments_screening_queue_1');
                } else {
                    echo str_replace('%NUM%', $screeningQueue, \Yii::t('amend', 'comments_screening_queue_x'));
                }
                echo '</div>';
            }
            $baseLink = UrlHelper::createMotionUrl($motion);
            foreach ($paragraph->getVisibleComments($screenAdmin) as $comment) {
                $commLink = UrlHelper::createMotionCommentUrl($comment);
                LayoutHelper::showComment($comment, $screenAdmin, $baseLink, $commLink);
            }

            if ($section->getMotion()->motionType->getCommentPolicy()->checkCurrUser()) {
                LayoutHelper::showCommentForm($form, $motion->getMyConsultation(), $section->sectionId, $paragraphNo);
            } elseif ($section->getMotion()->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
                echo '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in"></span>' . \Yii::t('amend', 'comments_please_log_in') . '</div>';
            }
            echo '</section>';
        }
    }


    echo '</div>';
}
