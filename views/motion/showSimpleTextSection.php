<?php
/**
 * @var \app\models\db\MotionSection $section
 */

use app\components\UrlHelper;
use yii\helpers\Html;

$hasLineNumbers = $section->consultationSetting->lineNumbers;
$paragraphs     = $section->getTextParagraphObjects($hasLineNumbers);
$classes        = ['paragraph'];
if ($hasLineNumbers) {
    $classes[] = 'lineNumbers';
    $lineNo    = $section->getFirstLineNo();
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
    echo '<section class="' . implode(' ', $parClasses) . '">';


    echo '<ul class="bookmarks">';
    $mayOpen = $section->motion->consultation->getCommentPolicy()->checkCurUserHeuristically();
    if (count($paragraph->comments) > 0 || $mayOpen) {
        echo '<li class="comment">';
        echo Html::a(count($paragraph->comments), '#', ['class' => 'shower']);
        echo Html::a(count($paragraph->comments), '#', ['class' => 'hider']);
        echo '</li>';
    }

    foreach ($paragraph->amendments as $amendment) {
        $amLink    = UrlHelper::createAmendmentUrl($amendment);
        $firstline = $amendment->getFirstAffectedLineOfParagraph_absolute();
        echo "<li class='amendment' data-first-line='" . $firstline . "'>';
                echo '<a data-id='" . $amendment->id . "' href='" . Html::encode($amLink) . "'>";
        echo Html::encode($amendment->titlePrefix) . "</a></li>\n";
    }

    echo '</ul>';

    echo '<div class="text">';
    $linesArr = [];
    foreach ($paragraph->lines as $line) {
        if ($section->consultationSetting->lineNumbers) {
            /** @var int $lineNo */
            $lineNoStr = '<span class="lineNumber">' . $lineNo++ . '</span>';
            $line      = str_replace('###LINENUMBER###', $lineNoStr, $line);
        }
        $linesArr[] = $line;
    }
    echo implode('<br>', $linesArr);
    echo '</div>';


    $mayOpen = $section->motion->consultation->getCommentPolicy()->checkCurUserHeuristically();
    if (count($paragraph->comments) > 0 || $mayOpen) {
        echo $this->render(
            'showComments',
            [
                'motion'       => $section->motion,
                'sectionId'    => $section->sectionId,
                'paragraphNo'  => $paragraphNo,
                'comments'     => $paragraph->comments,
            ]
        );
    }


    echo '</section>';
}
