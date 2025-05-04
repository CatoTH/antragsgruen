<?php

use app\components\HTMLTools;
use app\models\db\{Amendment, ConsultationMotionType, ConsultationSettingsTag, IMotion, Motion, User};
use app\models\settings\Privileges;
use CatoTH\HTML2OpenDocument\Spreadsheet;

/**
 * @var yii\web\View $this
 * @var IMotion[] $imotions
 * @var bool $textCombined
 */

/** @var \app\controllers\Base $controller */
$controller   = $this->context;
$consultation = $controller->consultation;

$DEBUG = false;

/** @var array<int, non-empty-array<IMotion>> $imotionByType */
$imotionByType = [];
foreach ($imotions as $imotion) {
    if (!isset($imotionByType[$imotion->getMyMotionType()->id])) {
        $imotionByType[$imotion->getMyMotionType()->id] = [];
    }
    $imotionByType[$imotion->getMyMotionType()->id][] = $imotion;
}


$params = \app\models\settings\AntragsgruenApp::getInstance();

/** @noinspection PhpUnhandledExceptionInspection */
$doc = new Spreadsheet([
    'tmpPath'   => $params->getTmpDir(),
    'trustHtml' => true,
]);

$row = 1;

foreach ($imotionByType as $byType) {
    $motionType = $byType[0]->getMyMotionType();

    $currCol = $firstCol = 1;

    $hasTags = ($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) > 0);
    $hasAgendaItems = false;
    $hasResponsibilities = false;
    foreach ($imotions as $imotion) {
        if (is_a($imotion, Motion::class) && $imotion->agendaItem) {
            $hasAgendaItems = true;
        }
        if ($imotion->responsibilityId || $imotion->responsibilityComment) {
            $hasResponsibilities = true;
        }
    }


    if ($hasAgendaItems) {
        $COL_AGENDA_ITEM = $currCol++;
    }
    $COL_PREFIX = $currCol++;
    $COL_INITIATOR = $currCol++;
    $COL_TEXTS = [];
    if ($textCombined) {
        $COL_TEXTS[] = $currCol++;
    } else {
        foreach ($motionType->motionSections as $section) {
            $COL_TEXTS[$section->id] = $currCol++;
        }
    }
    if ($hasTags) {
        $COL_TAGS = $currCol++;
    }
    $COL_CONTACT = $currCol++;
    $COL_PROCEDURE = $currCol++;
    $LAST_COL = $COL_PROCEDURE;
    if ($hasResponsibilities) {
        $COL_RESPONSIBILITY = $currCol++;
        $LAST_COL = $COL_RESPONSIBILITY;
    }


// Title

    $title = str_replace('%TITLE%', $motionType->titlePlural, Yii::t('export', 'all_motions_title'));
    $doc->setCell($row, $firstCol, Spreadsheet::TYPE_TEXT, $title);
    $doc->setCellStyle($row, $firstCol, [], [
        'fo:font-size' => '16pt',
        'fo:font-weight' => 'bold',
    ]);
    $doc->setMinRowHeight(1, 1.5);
    $row++;


// Heading

    if ($hasAgendaItems) {
        $doc->setCell($row, $COL_AGENDA_ITEM, Spreadsheet::TYPE_TEXT, Yii::t('export', 'agenda_item'));
        $doc->setCellStyle($row, $COL_AGENDA_ITEM, [], ['fo:font-weight' => 'bold']);
    }

    $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, Yii::t('export', 'prefix_short'));
    $doc->setCellStyle($row, $COL_PREFIX, [], ['fo:font-weight' => 'bold']);

    $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, Yii::t('export', 'initiator'));
    $doc->setColumnWidth($COL_INITIATOR, 6);

    if ($textCombined) {
        $doc->setCell($row, $COL_TEXTS[0], Spreadsheet::TYPE_TEXT, Yii::t('export', 'text'));
        $doc->setColumnWidth($COL_TEXTS[0], 10);
    } else {
        foreach ($motionType->motionSections as $section) {
            $doc->setCell($row, $COL_TEXTS[$section->id], Spreadsheet::TYPE_TEXT, $section->title);
            $doc->setColumnWidth($COL_TEXTS[$section->id], 10);
        }
    }
    if (isset($COL_TAGS)) {
        $doc->setCell($row, $COL_TAGS, Spreadsheet::TYPE_TEXT, Yii::t('export', 'tags'));
        $doc->setColumnWidth($COL_TAGS, 6);
    }
    $doc->setCell($row, $COL_CONTACT, Spreadsheet::TYPE_TEXT, Yii::t('export', 'contact'));
    $doc->setColumnWidth($COL_CONTACT, 6);
    $doc->setCell($row, $COL_PROCEDURE, Spreadsheet::TYPE_TEXT, Yii::t('export', 'procedure'));
    $doc->setColumnWidth($COL_PROCEDURE, 6);

    if ($hasResponsibilities) {
        $doc->setCell($row, $COL_RESPONSIBILITY, Spreadsheet::TYPE_TEXT, Yii::t('export', 'responsibility'));
        $doc->setColumnWidth($COL_RESPONSIBILITY, 6);
    }

    $doc->drawBorder($row - 1, $firstCol, $row, $LAST_COL, 1.5);


// Motions

    foreach ($byType as $imotion) {
        $row++;
        $doc->setMinRowHeight($row, 2);

        $initiatorNames = [];
        $initiatorContacts = [];
        foreach ($imotion->getInitiators() as $supp) {
            $initiatorNames[] = $supp->getNameWithResolutionDate(false);
            if ($supp->contactEmail !== '') {
                $initiatorContacts[] = $supp->contactEmail;
            }
            if ($supp->contactPhone !== '') {
                $initiatorContacts[] = $supp->contactPhone;
            }
        }

        if ($hasAgendaItems && is_a($imotion, Motion::class) && $imotion->agendaItem) {
            $doc->setCell($row, $COL_AGENDA_ITEM, Spreadsheet::TYPE_TEXT, $imotion->agendaItem->getShownCode(true));
        }
        $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $imotion->getFormattedTitlePrefix(\app\models\layoutHooks\Layout::CONTEXT_MOTION_LIST));
        $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, implode(', ', $initiatorNames));
        $doc->setCell($row, $COL_CONTACT, Spreadsheet::TYPE_TEXT, implode("\n", $initiatorContacts));

        if ($hasResponsibilities) {
            $responsibility = [];
            if ($imotion->responsibilityUser) {
                $user = $imotion->responsibilityUser;
                $responsibility[] = $user->name ? $user->name : $user->getAuthName();
            }
            if ($imotion->responsibilityComment) {
                $responsibility[] = $imotion->responsibilityComment;
            }
            $doc->setCell($row, $COL_RESPONSIBILITY, Spreadsheet::TYPE_TEXT, implode(', ', $responsibility));
        }


        if ($textCombined) {
            $text = '';
            foreach ($imotion->getSortedSections(true) as $section) {
                $text .= $section->getSettings()->title . "\n\n";
                if (is_a($imotion, Motion::class)) {
                    $text .= $section->getSectionType()->getMotionODS();
                } elseif (is_a($imotion, Amendment::class)) {
                    $text .= $section->getSectionType()->getAmendmentODS();
                }
                $text .= "\n\n";
            }
            $text = HTMLTools::correctHtmlErrors($text);
            $doc->setCell($row, $COL_TEXTS[0], Spreadsheet::TYPE_HTML, $text);
        } else {
            foreach ($motionType->motionSections as $section) {
                $text = '';
                if (User::havePrivilege($consultation, Privileges::PRIVILEGE_CONTENT_EDIT, null)) {
                    $sections = $imotion->getActiveSections(null, true);
                } else {
                    $sections = $imotion->getActiveSections();
                }
                foreach ($sections as $sect) {
                    if ($sect->sectionId === $section->id) {
                        if (is_a($imotion, Motion::class)) {
                            $text .= $sect->getSectionType()->getMotionODS();
                        } elseif (is_a($imotion, Amendment::class)) {
                            $text .= $sect->getSectionType()->getAmendmentODS();
                        }
                    }
                }
                $text = HTMLTools::correctHtmlErrors($text);
                $doc->setCell($row, $COL_TEXTS[$section->id], Spreadsheet::TYPE_HTML, $text);
            }
        }
        if (isset($COL_TAGS)) {
            $tags = [];
            foreach ($imotion->getPublicTopicTags() as $tag) {
                $tags[] = $tag->title;
            }
            $doc->setCell($row, $COL_TAGS, Spreadsheet::TYPE_TEXT, implode("\n", $tags));
        }
    }

    $row += 2;
}

echo $doc->finishAndGetDocument();
