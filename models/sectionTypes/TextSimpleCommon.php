<?php

namespace app\models\sectionTypes;

use app\views\pdfLayouts\{IPDFLayout, IPdfWriter};
use app\components\{HashedStaticCache, HTMLTools, LineSplitter, UrlHelper};
use app\components\diff\{AmendmentSectionFormatter, DataTypes\AffectedLineBlock, DiffRenderer};
use app\components\latex\{Content as LatexContent, Exporter};
use app\models\db\{Amendment, AmendmentSection, Consultation, Motion, MotionSection};
use yii\helpers\Html;

abstract class TextSimpleCommon extends Text {
    public function isEmpty(): bool
    {
        return ($this->section->getData() === '');
    }

    public function getSimple(bool $isRight, bool $showAlways = false): string
    {
        $sections = HTMLTools::sectionSimpleHTML($this->section->getData());
        $str      = '';
        foreach ($sections as $section) {
            $str .= '<div class="paragraph"><div class="text motionTextFormattings';
            if ($this->section->getSettings()->fixedWidth) {
                $str .= ' fixedWidthFont';
            }
            $str .= '">' . $section->html . '</div></div>';
        }
        return $str;
    }

    /**
     * @return array{groups: AffectedLineBlock[], sections: array}
     */
    private function getMaybeCachedDiffGroups(AmendmentSection $section, int $lineLength, int $firstLine): array
    {
        $originalText = $section->getOriginalMotionSection()?->getData() ?? '';
        $cacheDeps = [$originalText, $section->data, $firstLine, $lineLength, DiffRenderer::FORMATTING_CLASSES_ARIA];
        $cache = HashedStaticCache::getInstance('getMaybeCachedDiffGroups', $cacheDeps);

        // Only use cache for long motions
        if (strlen($originalText) < 10000) {
            $cache->setSkipCache(true);
        }

        return $cache->getCached(function () use ($section, $lineLength, $firstLine, $originalText) {
            $formatter = new AmendmentSectionFormatter();
            $formatter->setTextOriginal($originalText);
            $formatter->setTextNew($section->data);
            $formatter->setFirstLineNo($firstLine);
            $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES_ARIA);
            $diffSections = $formatter->getDiffSectionsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES_ARIA);

            return [
                'groups' => $diffGroups,
                'sections' => $diffSections,
            ];
        });
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function getAmendmentFormattedGlobalAlternative(): string
    {
        /** @var AmendmentSection $section */
        $section = $this->section;

        $str = '<div id="section_' . $section->sectionId . '" class="motionTextHolder">';
        $str .= '<h2 class="green">' . Html::encode($this->getTitle()) . '</h2>';
        $str .= '<div id="section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';

        $htmlSections = HTMLTools::sectionSimpleHTML($section->data);
        foreach ($htmlSections as $htmlSection) {
            $str .= '<div class="paragraph"><div class="text motionTextFormattings';
            if ($this->section->getSettings()->fixedWidth) {
                $str .= ' fixedWidthFont';
            }
            $str .= '" dir="' . ($section->getSettings()->getSettingsObj()->isRtl ? 'rtl' : 'ltr') . '">' . $htmlSection->html . '</div></div>';
        }

        $str .= '</div>';
        $str .= '</div>';

        return $str;
    }

    public function getAmendmentFormatted(string $htmlIdPrefix = ''): string
    {
        /** @var AmendmentSection $section */
        $section = $this->section;

        if ($section->getAmendment()->globalAlternative) {
            return $this->getAmendmentFormattedGlobalAlternative();
        }

        $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;
        $firstLine  = $section->getFirstLineNumber();

        $diffGroupsAndSections = $this->getMaybeCachedDiffGroups($section, $lineLength, $firstLine);

        if (count($diffGroupsAndSections['groups']) === 0) {
            return '';
        }

        $viewFullMode = ($section->getAmendment()->getExtraDataKey(Amendment::EXTRA_DATA_VIEW_MODE_FULL) === true || !$this->defaultOnlyDiff);
        $title = $this->getTitle();
        $str = '<div id="' . $htmlIdPrefix . 'section_' . $section->sectionId . '" class="motionTextHolder">';
        $str .= '<h2 class="green">' . Html::encode($title);
        $str .= '<div class="btn-group btn-group-xs greenHeaderDropDown amendmentTextModeSelector">
          <button type="button" class="btn btn-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="' . \Yii::t('amend', 'textmode_set') . '">
            <span class="sr-only">' . \Yii::t('amend', 'textmode_set') . '</span>
            <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-right">
          <li' . (!$viewFullMode ? ' class="selected"' : '') . '><a href="#" class="showOnlyChanges">' . \Yii::t('amend', 'textmode_only_changed') . '</a></li>
          <li' . ($viewFullMode ? ' class="selected"' : '') . '><a href="#" class="showFullText">' . \Yii::t('amend', 'textmode_full_text') . '</a></li>
          </ul>';
        $str .= '</h2>';
        $str       .= '<div id="' . $htmlIdPrefix . 'section_' . $section->sectionId . '_0" class="paragraph lineNumbers">';
        $wrapStart = '<section class="paragraph"><div class="text motionTextFormattings';
        if ($section->getSettings()->fixedWidth) {
            $wrapStart .= ' fixedWidthFont';
        }
        $wrapStart .= '" dir="' . ($section->getSettings()->getSettingsObj()->isRtl ? 'rtl' : 'ltr') . '">';
        $wrapEnd   = '</div></section>';
        if ($this->motionContext && $section->getAmendment() && $section->getAmendment()->motionId !== $this->motionContext->id) {
            $linkMotion = $section->getAmendment()->getMyMotion();
        } else {
            $linkMotion = null;
        }
        $str .= '<div class="onlyChangedText' . ($viewFullMode ? ' hidden' : '') . '">';
        $str .= TextSimple::formatDiffGroup($diffGroupsAndSections['groups'], $wrapStart, $wrapEnd, $firstLine, $linkMotion);
        $str .= '</div>';

        $str .= '<div class="fullMotionText text motionTextFormattings textOrig ';
        if ($section->getSettings()->fixedWidth) {
            $str .= 'fixedWidthFont ';
        }
        $str .= ($viewFullMode ? '' : ' hidden') . '">';
        $lineNo = $firstLine;
        foreach ($diffGroupsAndSections['sections'] as $diffSection) {
            $lineNumbers = substr_count($diffSection, '###LINENUMBER###');
            $str .= LineSplitter::replaceLinebreakPlaceholdersByMarkup($diffSection, !!$section->getSettings()->lineNumbers, $lineNo);
            $lineNo += $lineNumbers;
        }
        $str .= '</div>';

        $str       .= '</div>';
        $str       .= '</div>';

        return $str;
    }

    public function printMotionTeX(bool $isRight, LatexContent $content, Consultation $consultation): void
    {
        if ($this->isEmpty()) {
            return;
        }

        $tex = '';
        /** @var MotionSection $section */
        $section  = $this->section;
        $settings = $section->getSettings();

        $hasLineNumbers = !!$settings->lineNumbers;
        $lineLength     = ($hasLineNumbers ? $consultation->getSettings()->lineLength : 0);
        $fixedWidth     = !!$settings->fixedWidth;
        $firstLine      = $section->getFirstLineNumber();

        if ($settings->printTitle) {
            $tex .= '\subsection*{\AntragsgruenSection ' . Exporter::encodePlainString($this->getTitle()) . '}' . "\n";
        }

        $cacheDeps = [$hasLineNumbers, $lineLength, $firstLine, $fixedWidth, $section->getData()];
        $cache = HashedStaticCache::getInstance('printMotionTeX', $cacheDeps);
        $tex2 = $cache->getCached(function () use ($section, $fixedWidth, $hasLineNumbers, $firstLine) {
            $tex2 = '';
            if ($fixedWidth || $hasLineNumbers) {
                if ($hasLineNumbers) {
                    $tex2 .= "\\linenumbers\n";
                    $tex2 .= "\\resetlinenumber[" . $firstLine . "]\n";
                }

                $paragraphs = $section->getTextParagraphObjects($hasLineNumbers);
                foreach ($paragraphs as $paragraph) {
                    $tex2 .= Exporter::getMotionLinesToTeX($paragraph->lines) . "\n";
                }

                if ($hasLineNumbers) {
                    if (str_ends_with($tex2, "\\newline\n")) {
                        $tex2 = substr($tex2, 0, strlen($tex2) - 9) . "\n";
                    }
                    $tex2 .= "\n\\nolinenumbers\n";
                }
            } else {
                $paras = $section->getTextParagraphLines();
                foreach ($paras as $para) {
                    $html = str_replace('###LINENUMBER###', '', implode('', $para->lines));
                    $tex2 .= Exporter::getMotionLinesToTeX([$html]) . "\n";
                }
            }

            return Exporter::fixLatexErrorsInFinalDocument($tex2);
        });

        if ($isRight) {
            $content->textRight .= $tex . $tex2;
        } else {
            $content->textMain .= $tex . $tex2;
        }
    }

    public function printAmendmentTeX(bool $isRight, LatexContent $content): void
    {
        /** @var AmendmentSection $section */
        $section    = $this->section;
        $firstLine  = $section->getFirstLineNumber();
        $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;

        $cacheDeps = [
            $firstLine, $lineLength, $section->getOriginalMotionSection()->getData(), $section->data,
            $section->getAmendment()->globalAlternative
        ];
        $cache = HashedStaticCache::getInstance('printAmendmentTeX', $cacheDeps);
        $tex = $cache->getCached(function () use ($section, $firstLine, $lineLength) {
            $tex = '';
            if ($section->getAmendment()->globalAlternative) {
                $title = Exporter::encodePlainString($this->getTitle());
                if ($title == \Yii::t('motion', 'motion_text')) {
                    $titPattern = \Yii::t('amend', 'amendment_for_prefix');
                    $title      = str_replace('%PREFIX%', $section->getMotion()->getFormattedTitlePrefix(), $titPattern);
                }

                $tex .= '\subsection*{\AntragsgruenSection ' . Exporter::encodePlainString($title) . '}' . "\n";
                $tex .= Exporter::encodeHTMLString($section->data);
            } else {
                $formatter = new AmendmentSectionFormatter();
                $formatter->setTextOriginal($section->getOriginalMotionSection()?->getData() ?? '');
                $formatter->setTextNew($section->data);
                $formatter->setFirstLineNo($firstLine);

                $title = $this->getTitle();
                if ($title == \Yii::t('motion', 'motion_text')) {
                    $titPattern = \Yii::t('amend', 'amendment_for_prefix');
                    $title = str_replace('%PREFIX%', $section->getMotion()->getFormattedTitlePrefix(), $titPattern);
                }

                if ($this->defaultOnlyDiff) {
                    $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES);

                    if (count($diffGroups) > 0) {
                        $tex .= '\subsection*{\AntragsgruenSection ' . Exporter::encodePlainString($title) . '}' . "\n";
                        $html = TextSimple::formatDiffGroup($diffGroups, '', '<p></p>');

                        $tex .= Exporter::encodeHTMLString($html);
                    }
                } else {
                    $tex .= '\subsection*{\AntragsgruenSection ' . Exporter::encodePlainString($title) . '}' . "\n";

                    $diffs = $formatter->getDiffSectionsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES);

                    $lineNo = $firstLine;
                    foreach ($diffs as $diffSection) {
                        $lineNumbers = substr_count($diffSection, '###LINENUMBER###');
                        $html = LineSplitter::replaceLinebreakPlaceholdersByMarkup($diffSection, !!$section->getSettings()->lineNumbers, $lineNo);
                        $tex .= Exporter::encodeHTMLString($html);
                        $lineNo += $lineNumbers;
                    }
                }
            }
            return $tex;
        });

        if ($isRight) {
            $content->textRight .= $tex;
        } else {
            $content->textMain .= $tex;
        }
    }

    public function getMotionPlainText(): string
    {
        return HTMLTools::toPlainText($this->section->getData());
    }

    public function getAmendmentPlainText(): string
    {
        return HTMLTools::toPlainText($this->section->getData());
    }

    public function getMotionODS(): string
    {
        return $this->section->getData();
    }

    public static function formatAmendmentForOds(string $originalText, string $newText, int $firstLine, int $lineLength): string
    {
        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($originalText);
        $formatter->setTextNew($newText);
        $formatter->setFirstLineNo($firstLine);
        $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_CLASSES);

        $diff = static::formatDiffGroup($diffGroups, '', '', $firstLine);
        $diff = str_replace('<h4', '<br><h4', $diff);
        $diff = str_replace('</h4>', '</h4><br>', $diff);
        if (grapheme_substr($diff, 0, 4) === '<br>') {
            $diff = (string)grapheme_substr($diff, 4);
        }
        return $diff;
    }

    public function getAmendmentODS(): string
    {
        /** @var AmendmentSection $section */
        $section = $this->section;

        if ($section->getAmendment()->globalAlternative) {
            return $section->data;
        }

        $firstLine    = $section->getFirstLineNumber();
        $lineLength   = $section->getCachedConsultation()->getSettings()->lineLength;
        $originalData = $section->getOriginalMotionSection()?->getData() ?? '';
        return static::formatAmendmentForOds($originalData, $section->data, $firstLine, $lineLength);
    }

    public function matchesFulltextSearch(string $text): bool
    {
        $data = strip_tags($this->section->getData());
        return (grapheme_stripos($data, $text) !== false);
    }

    private function fixTcpdfAmendmentIssues(string $html): string
    {
        $replaces = [];
        $replaces['<ins '] = '<span ';
        $replaces['</ins>'] = '</span>';
        $replaces['<del '] = '<span ';
        $replaces['</del>'] = '</span>';
        $html = str_replace(array_keys($replaces), array_values($replaces), $html);

        // instead of <span class="strike"></span> TCPDF can only handle <s></s>
        // for striking through text
        $pattern = '/<span class="strike">(.*)<\/span>/iUs';
        $replace = '<s>${1}</s>';

        return preg_replace($pattern, $replace, $html);
    }

    /*
     * Workaround for https://github.com/CatoTH/antragsgruen/issues/137#issuecomment-305686868
     */
    public static function stripAfterInsertedNewlines(string $text): string
    {
        return preg_replace_callback('/((<br>\s*)+<\/ins>)(?<rest>.*)(?<end><\/[a-z]+>*)$/siu', function ($match) {
            $rest = $match['rest'];
            if (str_contains($rest, '<')) {
                return $match[0];
            } else {
                return '</ins>' . $match['end'];
            }
        }, $text);
    }

    /**
     * @param AffectedLineBlock[] $diffGroups
     */
    public static function formatDiffGroup(array $diffGroups, string $wrapStart = '', string $wrapEnd = '', int $firstLineOfSection = -1, ?Motion $linkMotion = null): string
    {
        $out = '';
        foreach ($diffGroups as $diff) {
            $text      = $diff->text;
            $text      = static::stripAfterInsertedNewlines($text);
            $hasInsert = $hasDelete = false;
            if (grapheme_strpos($text, 'class="inserted"') !== false) {
                $hasInsert = true;
            }
            if (grapheme_strpos($text, 'class="deleted"') !== false) {
                $hasDelete = true;
            }
            if (preg_match('/<ins( [^>]*)?>/siu', $text)) {
                $hasInsert = true;
            }
            if (preg_match('/<del( [^>]*)?>/siu', $text)) {
                $hasDelete = true;
            }
            $out            .= $wrapStart;
            $out            .= '<h4 class="lineSummary">';
            $isInsertedLine = (grapheme_strpos($diff->text, '###LINENUMBER###') === false);
            if ($isInsertedLine) {
                if (($hasInsert && $hasDelete) || (!$hasInsert && !$hasDelete)) {
                    $out .= \Yii::t('diff', 'after_line');
                } elseif ($hasDelete) {
                    $out .= \Yii::t('diff', 'after_line_del');
                } elseif ($hasInsert) {
                    if ($diff->lineTo < $firstLineOfSection) {
                        $out .= str_replace('#LINE#', (string)($diff->lineTo + 1), \Yii::t('diff', 'pre_line_ins'));
                    } else {
                        $out .= \Yii::t('diff', 'after_line_ins');
                    }
                }
            } elseif ($diff->lineFrom === $diff->lineTo) {
                if (($hasInsert && $hasDelete) || (!$hasInsert && !$hasDelete)) {
                    $out .= \Yii::t('diff', 'in_line');
                } elseif ($hasDelete) {
                    $out .= \Yii::t('diff', 'in_line_del');
                } elseif ($hasInsert) {
                    $out .= \Yii::t('diff', 'in_line_ins');
                }
            } else {
                if (($hasInsert && $hasDelete) || (!$hasInsert && !$hasDelete)) {
                    $out .= \Yii::t('diff', 'line_to');
                } elseif ($hasDelete) {
                    $out .= \Yii::t('diff', 'line_to_del');
                } elseif ($hasInsert) {
                    $out .= \Yii::t('diff', 'line_to_ins');
                }
            }
            $lineFrom = ($diff->lineFrom < $firstLineOfSection ? $firstLineOfSection : $diff->lineFrom);
            $out      = str_replace(['#LINETO#', '#LINEFROM#'], [(string)$diff->lineTo, (string)$lineFrom], $out);
            if ($linkMotion) {
                $link = UrlHelper::createMotionUrl($linkMotion);
                $out .= ' <span class="linkedMotion">(' . Html::a(Html::encode($linkMotion->getTitleWithPrefix()), $link) . ')</span>';
            }
            $out      .= ':</h4><div>';
            if ($text[0] != '<') {
                $out .= '<p>' . $text . '</p>';
            } else {
                $out .= $text;
            }
            $out .= '</div>';
            $out .= $wrapEnd;
        }

        $aria        = str_replace('%DEL%', \Yii::t('diff', 'space'), \Yii::t('diff', 'aria_del'));
        $strSpaceDel = '<del class="space" aria-label="' . $aria . '">[' . \Yii::t('diff', 'space') . ']</del>';

        $aria          = str_replace('%DEL%', \Yii::t('diff', 'space'), \Yii::t('diff', 'aria_del'));
        $strNewlineDel = '<del class="space" aria-label="' . $aria . '">[' . \Yii::t('diff', 'newline') . ']</del>';

        $aria        = str_replace('%INS%', \Yii::t('diff', 'space'), \Yii::t('diff', 'aria_ins'));
        $strSpaceIns = '<ins class="space" aria-label="' . $aria . '">[' . \Yii::t('diff', 'space') . ']</ins>';

        $aria          = str_replace('%INS%', \Yii::t('diff', 'newline'), \Yii::t('diff', 'aria_ins'));
        $strNewlineIns = '<ins class="space" aria-label="' . $aria . '">[' . \Yii::t('diff', 'newline') . ']</ins>';

        $out  = preg_replace('/<del[^>]*> <\/del>/siu', $strSpaceDel, $out);
        $out  = preg_replace('/<ins[^>]*> <\/ins>/siu', $strSpaceIns, $out);
        $out  = preg_replace('/<del[^>]*><br><\/del>/siu', $strNewlineDel . '<del><br></del>', $out);
        $out  = preg_replace('/<ins[^>]*><br><\/ins>/siu', $strNewlineIns . '<ins><br></ins>', $out);
        $out  = str_replace($strSpaceDel . $strNewlineIns, $strNewlineIns, $out);
        $out  = str_replace($strSpaceDel . '<ins></ins><br>', '<br>', $out);
        $out  = str_replace('###LINENUMBER###', '', $out);
        $repl = '<br></p></div>';
        if (grapheme_substr($out, grapheme_strlen($out) - grapheme_strlen($repl), (int)grapheme_strlen($repl)) === $repl) {
            $out = grapheme_substr($out, 0, grapheme_strlen($out) - grapheme_strlen($repl)) . '</p></div>';
        }
        return $out;
    }

    public function printAmendmentToPDF(IPDFLayout $pdfLayout, IPdfWriter $pdf): void
    {
        $pdf->setFont('helvetica', '', 12);

        /** @var AmendmentSection $section */
        $section = $this->section;
        if ($section->getAmendment()->globalAlternative) {
            if ($section->getSettings()->printTitle) {
                $pdfLayout->printSectionHeading($this->getTitle());
                $pdf->ln(7);
            }

            $html = str_replace('</li>', '<br></li>', $section->data);
            $html = str_replace('<ol', '<br><ol', $html);
            $html = str_replace('<ul', '<br><ul', $html);

            $pdf->writeHTMLCell(170, 0, 24, null, $html, 0, 1, false, true, '', true);
        } else {
            $firstLine  = $section->getFirstLineNumber();
            $lineLength = $section->getCachedConsultation()->getSettings()->lineLength;

            $formatter = new AmendmentSectionFormatter();
            $formatter->setTextOriginal($section->getOriginalMotionSection()?->getData() ?? '');
            $formatter->setTextNew($section->data);
            $formatter->setFirstLineNo($firstLine);

            if ($this->defaultOnlyDiff) {
                $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, DiffRenderer::FORMATTING_INLINE);

                if (count($diffGroups) > 0) {
                    if ($section->getSettings()->printTitle) {
                        $pdfLayout->printSectionHeading($this->getTitle());
                        $pdf->ln(7);
                    }

                    $html = self::formatDiffGroup($diffGroups);
                    $html = self::fixTcpdfAmendmentIssues($html);

                    $pdf->writeHTMLCell(170, 0, 24, null, $html, 0, 1, false, true, '', true);
                }
            } else {
                if ($section->getSettings()->printTitle) {
                    $pdfLayout->printSectionHeading($this->getTitle());
                    $pdf->ln(7);
                }

                $diffs = $formatter->getDiffSectionsWithNumbers($lineLength, DiffRenderer::FORMATTING_INLINE);
                $html = '';
                $lineNo = $firstLine;
                foreach ($diffs as $diffSection) {
                    $lineNumbers = substr_count($diffSection, '###LINENUMBER###');
                    $html .= LineSplitter::replaceLinebreakPlaceholdersByMarkup($diffSection, !!$section->getSettings()->lineNumbers, $lineNo);
                    $lineNo += $lineNumbers;
                }

                $html = self::fixTcpdfAmendmentIssues($html);

                $pdf->writeHTMLCell(170, 0, 24, null, $html, 0, 1, false, true, '', true);
            }
        }
        $pdf->ln(7);
    }
}
