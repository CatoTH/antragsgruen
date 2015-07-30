<?php
/**
 * @var \app\models\db\MotionSection $section
 * @var int[] $openedComments
 * @var null|CommentForm $commentForm
 */

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\User;
use app\models\forms\CommentForm;
use app\views\motion\LayoutHelper;
use yii\helpers\Html;

$paragraphs = $section->getTextParagraphObjects(false, true, true);
$classes    = ['paragraph'];

/** @var Amendment[] $amendmentsById */
$amendmentsById = [];

$paras          = $section->getTextParagraphs();
$paraData       = [];
foreach ($paras as $paraNo => $paraStr) {
    $origTokenized = \app\components\diff\Diff::tokenizeLine($paraStr);
    $origArr       = preg_split('/\R/', $origTokenized);
    $modifications = [];
    foreach ($origArr as $x) {
        $modifications[] = [
            'orig'          => $x,
            'modifications' => [],
        ];
    }
    $paraData[$paraNo] = [
        'orig'              => $paraStr,
        'origTokenized'     => $origTokenized,
        'modifications'     => $modifications,
        'amendmentSections' => [],
    ];
}

$diffEngine = new \app\components\diff\Engine();
foreach ($section->amendingSections as $sect) {
    $amendmentsById[$sect->amendmentId] = $sect->amendment;

    $affectedParas = $sect->getAffectedParagraphs($paras);
    foreach ($affectedParas as $amendPara => $amendText) {
        $newTokens  = \app\components\diff\Diff::tokenizeLine($amendText);
        $diffTokens = $diffEngine->compareStrings($paraData[$amendPara]['origTokenized'], $newTokens);
        $origNo     = 0;
        foreach ($diffTokens as $token) {
            if ($token[1] == \app\components\diff\Engine::INSERTED) {
                if ($token[0] != '') {
                    $insStr = '<ins>' . $token[0] . '</ins>';
                    if ($origNo == 0) {
                        // @TODO
                    } else {
                        $pre = $origNo - 1;
                        if (!isset($paraData[$amendPara]['modifications'][$pre]['modifications'][$sect->amendmentId])) {
                            $orig                                                                             = $paraData[$amendPara]['modifications'][$pre]['orig'];
                            $paraData[$amendPara]['modifications'][$pre]['modifications'][$sect->amendmentId] = $orig;
                        }
                        $paraData[$amendPara]['modifications'][$pre]['modifications'][$sect->amendmentId] .= $insStr;
                    }
                }
            }
            if ($token[1] == \app\components\diff\Engine::DELETED) {
                if ($token[0] != '') {
                    $delStr = '<del>' . $token[0] . '</del>';
                    if (!isset($paraData[$amendPara]['modifications'][$origNo]['modifications'][$sect->amendmentId])) {
                        $paraData[$amendPara]['modifications'][$origNo]['modifications'][$sect->amendmentId] = '';
                    }
                    $paraData[$amendPara]['modifications'][$origNo]['modifications'][$sect->amendmentId] .= $delStr;
                }
                $origNo++;
            }
            if ($token[1] == \app\components\diff\Engine::UNMODIFIED) {
                $origNo++;
            }
        }
    }
}

$groupedParaData = [];
foreach ($paraData as $paraNo => $para) {
    $paraG            = [];
    $pending          = '';
    $pendingCurrAmend = 0;
    foreach ($para['modifications'] as $modi) {
        if (count($modi['modifications']) > 1) {
            echo 'PROBLEM: ';
            var_dump($modi);
        } elseif (count($modi['modifications']) == 1) {
            $keys = array_keys($modi['modifications']);
            if ($keys[0] != $pendingCurrAmend) {
                $paraG[]          = [
                    'amendment' => $pendingCurrAmend,
                    'text'      => $pending,
                ];
                $pending          = '';
                $pendingCurrAmend = $keys[0];
            }
            $pending .= $modi['modifications'][$keys[0]];
        } else {
            if (0 != $pendingCurrAmend) {
                $paraG[]          = [
                    'amendment' => $pendingCurrAmend,
                    'text'      => $pending,
                ];
                $pending          = '';
                $pendingCurrAmend = 0;
            }
            $pending .= $modi['orig'];
        }
    }
    $paraG[]                  = [
        'amendment' => $pendingCurrAmend,
        'text'      => $pending,
    ];
    $groupedParaData[$paraNo] = $paraG;
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
    echo '<div class="text">';

    foreach ($groupedParaData[$paragraphNo] as $part) {
        $text = $part['text'];
        $text = preg_replace('/<(del|ins)>(<\/?(li|ul|ol)>)<\/(del|ins)>/siu', '\2', $text);
        $text = str_replace('</ins><ins>', '', $text);
        $text = str_replace('</del><del>', '', $text);

        if ($part['amendment'] > 0) {
            $amendment = $amendmentsById[$part['amendment']];
            $url = UrlHelper::createAmendmentUrl($amendment);
            $refStr = ' <span class="amendmentRef">[' . Html::a($amendment->titlePrefix, $url) . ']</span> ';
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
            } else {
                $text .= $refStr;
            }
        }

        echo $text;
    }

    echo '</div>';
    echo '</section>';
}
