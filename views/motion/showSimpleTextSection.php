<?php
/**
 * @var \app\models\db\MotionSection $section
 * @var int[] $openedComments
 * @var null|CommentForm $commentForm
 */

use app\components\HTMLTools;
use app\components\UrlHelper;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\MotionComment;
use app\models\db\User;
use app\models\forms\CommentForm;
use yii\helpers\Html;

$motion         = $section->getMotion();
$hasLineNumbers = $section->getSettings()->lineNumbers;
$paragraphs     = $section->getTextParagraphObjects($hasLineNumbers, true, true);
$screenAdmin    = User::havePrivilege($section->getConsultation(), User::PRIVILEGE_SCREENING);
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
    if ($section->getSettings()->hasComments === ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS) {
        $mayOpen     = $section->getMotion()->motionType->getCommentPolicy()->checkCurrUser(true, true);
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

    echo '<div class="text motionTextFormattings textOrig';
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

    $comment = $motion->getPrivateComment($section->sectionId, $paragraphNo);
    ?>
    <section class="privateParagraphNoteHolder">
        <?php
        if (!$comment) {
            ?>
            <div class="privateParagraphNoteOpener hidden">
                <button class="btn btn-link btn-xs">
                    <span class="glyphicon glyphicon-pushpin"></span>
                    <?= \Yii::t('motion', 'private_notes') ?>
                </button>
            </div>
            <?php
        }
        if ($comment) {
            $id = 'privateNote_' . $section->sectionId . '_' . $paragraphNo;
            ?>
            <blockquote class="privateParagraph<?= $comment ? '' : ' hidden' ?>" id="<?= $id ?>">
                <button class="btn btn-link btn-xs btnEdit"><span class="glyphicon glyphicon-edit"></span></button>
                <?= HTMLTools::textToHtmlWithLink($comment ? $comment->text : '') ?>
            </blockquote>
            <?php
        }
        ?>
        <?= Html::beginForm('', 'post', ['class' => 'form-inline hidden']) ?>
        <label>
            <?= \Yii::t('motion', 'private_notes') ?>
            <textarea class="form-control" name="noteText"
            ><?= Html::encode($comment ? $comment->text : '') ?></textarea>
        </label>
        <input type="hidden" name="paragraphNo" value="<?= $paragraphNo ?>">
        <input type="hidden" name="sectionId" value="<?= $section->sectionId ?>">
        <button type="submit" name="savePrivateNote" class="btn btn-success">
            <?= \Yii::t('base', 'save') ?>
        </button>
        <?= Html::endForm() ?>
    </section>
    <?php
    echo '</div>';

    foreach ($paragraph->amendmentSections as $amendmentSection) {
        $amendment = \app\models\db\Consultation::getCurrent()->getAmendment($amendmentSection->amendmentId);
        echo '<div class="text motionTextFormattings textAmendment hidden amendment' . $amendment->id;
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

        // Limit memory consumption
        unset($amParas);
        unset($amendmentSection->amendmentSection);
        unset($amendmentSection);
        unset($amendment);
        gc_collect_cycles();
    }

    if ($section->getSettings()->hasComments === ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS) {
        if (count($paragraph->comments) > 0 || $section->getMotion()->motionType->getCommentPolicy()) {
            echo '<section class="commentHolder" data-antragsgruen-widget="frontend/Comments">';
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
                $form = new \app\models\forms\CommentForm($motion->getMyMotionType(), null);
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
                    echo \Yii::t('amend', 'comments_screening_queue_1');
                } else {
                    echo str_replace('%NUM%', $screeningQueue, \Yii::t('amend', 'comments_screening_queue_x'));
                }
                echo '</div>';
            }
            foreach ($paragraph->getVisibleComments($screenAdmin, null) as $comment) {
                echo $this->render('@app/views/motion/_comment', ['comment' => $comment]);
            }

            echo $form->renderFormOrErrorMessage();

            echo '</section>';
        }
    }


    echo '</div>';
}
