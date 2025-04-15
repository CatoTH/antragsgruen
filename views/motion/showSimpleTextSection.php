<?php
/**
 * @var \app\models\db\MotionSection $section
 * @var int[] $openedComments
 * @var null|CommentForm $commentForm
 */

use app\components\UrlHelper;
use app\models\settings\{PrivilegeQueryContext, Privileges};
use app\models\db\{ConsultationMotionType, ConsultationSettingsMotionSection, MotionComment, User};
use app\models\forms\CommentForm;
use yii\helpers\Html;

$consultation   = $section->getConsultation();
$motion         = $section->getMotion();
$motionType     = $motion->getMyMotionType();
$hasLineNumbers = $section->getSettings()->lineNumbers;
$paragraphs     = $section->getTextParagraphObjects($hasLineNumbers, true, true, true);
$screenAdmin    = User::havePrivilege($section->getConsultation(), Privileges::PRIVILEGE_SCREENING, PrivilegeQueryContext::motion($motion));
$classes        = ['paragraph'];
if ($hasLineNumbers) {
    $classes[] = 'lineNumbers';
    $lineNo    = $section->getFirstLineNumber();
}

foreach ($paragraphs as $paragraphNo => $paragraph) {
    $parClasses = $classes;
    $firstLine = $paragraph->lines[0] ?? '';
    if (str_starts_with($firstLine, '<ul>')) {
        $parClasses[] = 'list';
    } elseif (str_starts_with($firstLine, '<ol>')) {
        $parClasses[] = 'list';
    } elseif (str_starts_with($firstLine, '<blockquote>')) {
        $parClasses[] = 'blockquote';
    }
    if (in_array($paragraphNo, $openedComments)) {
        $parClasses[] = 'commentsOpened';
    }
    $id = 'section_' . $section->sectionId . '_' . $paragraphNo;
    echo '<div class="' . implode(' ', $parClasses) . '" id="' . $id . '">';

    $hasComments = ($section->getSettings()->hasComments === ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS);
    $hasAmendments = (count($paragraph->amendmentSections) > 0);
    if ($hasComments || $hasAmendments) {
        echo '<ul class="bookmarks">';
        if ($hasComments) {
            $mayOpen = $motionType->maySeeIComments();
            $numComments = $paragraph->getNumOfAllVisibleComments($screenAdmin);
            if ($numComments > 0 || $mayOpen) {
                echo '<li class="comment">';
                $str  = '<span class="glyphicon glyphicon-comment"></span>';
                $str  .= '<span class="count" data-count="' . $numComments . '"></span>';
                $zero = '';
                if ($numComments === 0) {
                    $zero .= ' zero';
                }
                echo Html::a($str, '#', ['class' => 'shower' . $zero]);
                echo Html::a($str, '#', ['class' => 'hider active' . $zero]);
                echo '</li>';
            }
        }

        foreach ($paragraph->amendmentSections as $amendmentSection) {
            $amendment = $consultation->getAmendment($amendmentSection->amendmentId);
            $amLink    = UrlHelper::createAmendmentUrl($amendment);
            $firstline = $amendmentSection->firstAffectedLine;
            echo '<li class="amendment amendment' . $amendment->id . '" data-first-line="' . $firstline . '">';
            echo '<a data-id="' . $amendment->id . '" href="' . Html::encode($amLink) . '">';
            echo ($amendment->getFormattedTitlePrefix() ? Html::encode($amendment->titlePrefix) : '&nbsp;');
            echo \app\models\layoutHooks\Layout::getAmendmentBookmarkName($amendment);
            echo "</a></li>\n";
        }

        echo '</ul>';
    }

    echo '<div class="text motionTextFormattings textOrig';
    if ($section->getSettings()->fixedWidth) {
        echo ' fixedWidthFont';
    }
    echo '" dir="' . ($section->getSettings()->getSettingsObj()->isRtl ? 'rtl' : 'ltr') . '">';
    if ($section->getSettings()->fixedWidth || $hasLineNumbers) {
        foreach ($paragraph->lines as $i => $line) {
            if ($section->getSettings()->lineNumbers) {
                /** @var int $lineNo */
                $lineNoStr = '<span class="lineNumber" data-line-number="' . $lineNo++ . '" aria-hidden="true"></span>';
                $line      = str_replace('###LINENUMBER###', $lineNoStr, $line);
            } else {
                $line      = str_replace('###LINENUMBER###', '', $line);
            }
            $line   = str_replace('<br>', '', $line);
            $first3 = substr($line, 0, 3);
            if ($i > 0 && !in_array($first3, ['<ol', '<ul', '<p>', '<di'])) {
                echo '<br>';
            }
            if ($consultation->getSettings()->externalLinksNewWindow) {
                echo preg_replace('/<a( href=["\']([^"\']*)["\']>)/iu', '<a target="_blank"$1', $line);
            } else {
                echo $line;
            }
        }
    } else {
        if ($consultation->getSettings()->externalLinksNewWindow) {
            echo preg_replace('/<a( href=["\']([^"\']*)["\']>)/iu', '<a target="_blank"$1', $paragraph->origStr);
        } else {
            echo $paragraph->origStr;
        }
    }

    // Only static HTML should be returned from this view, so we can safely cache it.
    echo '<!--PRIVATE_NOTE_' . $section->sectionId . '_' . $paragraphNo . '-->';

    if ($section->getSettings()->hasAmendments &&
        in_array($motionType->amendmentMultipleParagraphs, [ConsultationMotionType::AMEND_PARAGRAPHS_SINGLE_PARAGRAPH, ConsultationMotionType::AMEND_PARAGRAPHS_SINGLE_CHANGE])) {
        echo '<!--AMENDMENT_LINK_' . $section->sectionId . '_' . $paragraph->paragraphNo . '-->';
    }

    echo '</div>';

    foreach ($paragraph->amendmentSections as $amendmentSection) {
        $amendment = \app\models\db\Consultation::getCurrent()->getAmendment($amendmentSection->amendmentId);
        echo '<div class="text motionTextFormattings textAmendment hidden amendment' . $amendment->id;
        if ($section->getSettings()->fixedWidth) {
            echo ' fixedWidthFont';
        }
        echo '" dir="' . ($section->getSettings()->getSettingsObj()->isRtl ? 'rtl' : 'ltr') . '">';
        echo '<div class="preamble"><a href="' . Html::encode(UrlHelper::createAmendmentUrl($amendment)) . '">';
        echo '<h3><span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>' . Html::encode($amendment->getShortTitle(false)) . '</h3>';
        echo ', ' . Yii::t('amend', 'initiated_by') . ': ' . Html::encode($amendment->getInitiatorsStr());
        $amParas = $amendment->getChangedParagraphs($motion->getActiveSections(), true);
        if (count($amParas) > 1) {
            echo '<div class="moreAffected">';
            echo str_replace('%num%', count($amParas), Yii::t('amend', 'affects_x_paragraphs'));
            echo '</div>';
        }
        echo '</a></div>';
        echo str_replace('###LINENUMBER###', '', $amendmentSection->strDiff);
        echo '</div>';

        // Limit memory consumption
        unset($amParas);
        unset($amendmentSection->amendmentSection);
        unset($amendmentSection);
        unset($amendment);
        gc_collect_cycles();
    }

    if ($section->getSettings()->hasComments === ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS && $motionType->maySeeIComments()) {
        if (count($paragraph->comments) > 0 || $motionType->getCommentPolicy()) {
            echo '<section class="commentHolder" data-antragsgruen-widget="frontend/Comments">';
            $motion = $section->getMotion();
            $form   = $commentForm;

            if (in_array($paragraphNo, $openedComments)) {
                $screening = Yii::$app->session->getFlash('screening', null, true);
                if ($screening) {
                    echo '<div class="alert alert-success" role="alert">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <span class="sr-only">Success:</span>
                ' . Html::encode($screening) . '
            </div>';
                }
            }

            if ($form === null || $form->paragraphNo != $paragraphNo || $form->sectionId != $section->sectionId) {
                $form = new CommentForm($motion, null);
                $form->setDefaultData($paragraphNo, $section->sectionId, User::getCurrentUser());
            }

            $screeningQueue = 0;
            foreach ($paragraph->comments as $comment) {
                if ($comment->status === MotionComment::STATUS_SCREENING) {
                    $screeningQueue++;
                }
            }
            if ($screeningQueue > 0) {
                echo '<div class="commentScreeningQueue">';
                if ($screeningQueue === 1) {
                    echo Yii::t('amend', 'comments_screening_queue_1');
                } else {
                    echo str_replace('%NUM%', $screeningQueue, Yii::t('amend', 'comments_screening_queue_x'));
                }
                echo '</div>';
            }
            foreach ($paragraph->getVisibleComments($screenAdmin, null) as $comment) {
                echo $this->render('@app/views/shared/comment', ['comment' => $comment]);
            }

            echo $form->renderFormOrErrorMessage();

            echo '</section>';
        }
    }


    echo '</div>';
}
