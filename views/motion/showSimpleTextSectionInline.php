<?php
/**
 * @var \app\models\db\MotionSection $section
 * @var int[] $openedComments
 * @var null|CommentForm $commentForm
 */

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\forms\CommentForm;
use yii\helpers\Html;

$paragraphs = $section->getTextParagraphObjects(false, true, true);
$classes    = ['paragraph'];

/** @var Amendment[] $amendmentsById */
$amendmentsById = [];

$merger = new \app\components\diff\AmendmentDiffMerger();
$merger->initByMotionSection($section);


$diffEngine = new \app\components\diff\Engine();
foreach ($section->amendingSections as $sect) {
    $amendmentsById[$sect->amendmentId] = $sect->amendment;
    $merger->addAmendmentSection($sect);
}

$groupedParaData = $merger->getGroupedParagraphData();

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
    echo '<div class="text">';

    foreach ($groupedParaData[$paragraphNo] as $part) {
        $text = $part['text'];

        if ($part['amendment'] > 0) {
            $amendment = $amendmentsById[$part['amendment']];
            $url       = UrlHelper::createAmendmentUrl($amendment);
            $refStr    = ' <span class="amendmentRef">[' . Html::a($amendment->titlePrefix, $url) . ']</span> ';
            if (mb_strpos($text, '</ul>') !== false) {
                $x = explode('</ul>', $text);
                for ($i = 0; $i < count($x); $i++) {
                    if (trim($x[$i]) != '') {
                        if (mb_strpos($x[$i], '</li>') !== false) {
                            $x[$i] = str_replace('</li>', $refStr . '</li>', $x[$i]);
                        } else {
                            $x[$i] .= $refStr;
                        }
                    }
                }
                $text = implode('</ul>', $x);
            } elseif (mb_strpos($text, '</p>') !== false) {
                $text = str_replace('</p>', $refStr . '</p>', $text);
            } else {
                $text .= $refStr;
            }
        }

        echo $text;
    }

    echo '</div>';
    echo '</section>';
}
