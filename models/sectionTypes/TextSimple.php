<?php

namespace app\models\sectionTypes;

use app\components\HTMLTools;
use app\models\exceptions\FormError;
use app\components\UrlHelper;
use yii\helpers\Html;

class TextSimple extends ISectionType
{

    /**
     * @return string
     */
    public function getFormField()
    {
        $type = $this->section->consultationSetting;

        $str = '<fieldset class="form-group wysiwyg-textarea"';
        $str .= ' data-maxLen="' . $type->maxLen . '"';
        $str .= ' data-fullHtml="0"';
        $str .= '><label for="sections_' . $type->id . '">' . Html::encode($type->title) . '</label>';

        if ($type->maxLen > 0) {
            $str .= '<div class="max_len_hint">';
            $str .= '<div class="calm">Maximale Länge: ' . $type->maxLen . ' Zeichen</div>';
            $str .= '<div class="alert">Text zu lang - maximale Länge: ' . $type->maxLen . ' Zeichen</div>';
            $str .= '</div>';
        }

        $str .= '<div class="textFullWidth">';
        $str .= '<div><textarea id="sections_' . $type->id . '" name="sections[' . $type->id . ']" rows="5" cols="80">';
        $str .= Html::encode($this->section->data);
        $str .= '</textarea></div></div>';
        $str .= '</fieldset>';

        return $str;
    }

    /**
     * @param $data
     * @throws FormError
     */
    public function setData($data)
    {
        $this->section->data = HTMLTools::cleanSimpleHtml($data);
    }

    /**
     * @return string
     */
    public function showSimple()
    {
        $sections = HTMLTools::sectionSimpleHTML($this->section->data);
        $str      = '';
        foreach ($sections as $section) {
            $str .= '<div class="content">' . $section . '</div>';
        }
        return $str;
    }

    /**
     * @return string
     */
    public function showMotionView()
    {
        $hasLineNumbers = $this->section->consultationSetting->lineNumbers;
        $paragraphs     = $this->section->getTextParagraphObjects($hasLineNumbers);
        $classes        = ['paragraph'];
        if ($hasLineNumbers) {
            $classes[] = 'lineNumbers';
            $lineNo    = $this->section->getFirstLineNo();
        }
        $str = '';
        foreach ($paragraphs as $paragraph) {
            $parClasses = $classes;
            if (mb_stripos($paragraph->lines[0], '<ul>') === 0) {
                $parClasses[] = 'list';
            } elseif (mb_stripos($paragraph->lines[0], '<ol>') === 0) {
                $parClasses[] = 'list';
            } elseif (mb_stripos($paragraph->lines[0], '<blockquote>') === 0) {
                $parClasses[] = 'blockquote';
            }
            $str .= '<section class="' . implode(' ', $parClasses) . '">';


            $str .= '<ul class="bookmarks">';
            $mayOpen = $this->section->motion->consultation->getCommentPolicy()->checkCurUserHeuristically();
            if (count($paragraph->comments) > 0 || $mayOpen) {
                $str .= '<li class="comment">';
                $str .= Html::a(count($paragraph->comments), '#', ['class' => 'shower']);
                $str .= Html::a(count($paragraph->comments), '#', ['class' => 'hide']);
                $str .= '</li>';
            }

            foreach ($paragraph->amendments as $amendment) {
                $amLink    = UrlHelper::createAmendmentUrl($amendment);
                $firstline = $amendment->getFirstAffectedLineOfParagraph_absolute();
                $str .= "<li class='amendment' data-first-line='" . $firstline . "'>';
                $str .= '<a data-id='" . $amendment->id . "' href='" . Html::encode($amLink) . "'>";
                $str .= Html::encode($amendment->titlePrefix) . "</a></li>\n";
            }

            $str .= '</ul>';

            $str .= '<div class="text">';
            $linesArr = [];
            foreach ($paragraph->lines as $line) {
                if ($this->section->consultationSetting->lineNumbers) {
                    /** @var int $lineNo */
                    $lineNoStr = '<span class="lineNumber">' . $lineNo++ . '</span>';
                    $line      = str_replace('###LINENUMBER###', $lineNoStr, $line);
                }
                $linesArr[] = $line;
            }
            $str .= implode('<br>', $linesArr);
            $str .= '</div>';
            $str .= '</section>';
        }
        return $str;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ($this->section->data == '');
    }
}
