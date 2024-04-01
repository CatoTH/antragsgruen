<?php

use CatoTH\HTML2OpenDocument\Spreadsheet;
use app\components\{IMotionStatusFilter, StringSplitter, UrlHelper};
use app\models\db\{Amendment, ConsultationAgendaItem, Motion};

/**
 * @var $this yii\web\View
 * @var array $items
 * @var IMotionStatusFilter $filter
 */

/** @var \app\controllers\Base $controller */
const agendaColor = '#fff2cc';
const motionColor = '#38761d';
$controller   = $this->context;
$consultation = $controller->consultation;

$DEBUG = false;

/** @var \app\models\settings\AntragsgruenApp $params */
$params = Yii::$app->params;

$doc = new Spreadsheet([
    'tmpPath'   => $params->getTmpDir(),
    'trustHtml' => true,
]);

$currCol = 0;

$COL_PREFIX    = $currCol++;
$COL_TITLE     = $currCol++;
$COL_INITIATOR = $currCol++;
$COL_EMAIL     = $currCol++;
$COL_PHONE     = $currCol++;
$COL_LINK      = $currCol++;

$colLimit = $currCol;

$doc->setCell(0, $COL_PREFIX, Spreadsheet::TYPE_HTML, Yii::t('export', 'prefix_short'));
$doc->setCell(0, $COL_TITLE, Spreadsheet::TYPE_HTML, Yii::t('export', 'title'));
$doc->setCell(0, $COL_INITIATOR, Spreadsheet::TYPE_HTML, Yii::t('export', 'InitiatorSingle'));
$doc->setCell(0, $COL_EMAIL, Spreadsheet::TYPE_HTML, Yii::t('export', 'email'));
$doc->setCell(0, $COL_PHONE, Spreadsheet::TYPE_HTML, Yii::t('export', 'phone'));
$doc->setCell(0, $COL_LINK, Spreadsheet::TYPE_HTML, Yii::t('export', 'motion'));

$doc->setColumnWidth($COL_PREFIX, 2);
$doc->setColumnWidth($COL_TITLE, 6);
$doc->setColumnWidth($COL_INITIATOR, 6);
$doc->setColumnWidth($COL_EMAIL, 4);
$doc->setColumnWidth($COL_PHONE, 4);

$row = 1;

$fill = function ($cellAttributes, $textAttributes) use ($doc, &$row, $colLimit) {
    for ($col = 0; $col < $colLimit; $col++) {
        $doc->setCellStyle($row, $col, $cellAttributes, $textAttributes);
    }
};

foreach ($items as $item) {
    if ($item instanceof ConsultationAgendaItem) {
        $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $item->getShownCode(true));
        $fill (['fo:background-color' => agendaColor], []);
    } else {
        if ($item instanceof Motion || $item instanceof Amendment) {
            $title  = $item->title;
            $prefix = $item->getFormattedTitlePrefix();

            if ($item instanceof Motion) {
                $subject = str_replace('%MOTION%', $prefix, Yii::t('export', 'mail_motion_x'));
                $body    = $prefix . ' ("' . $title . '")';
            } else {
                $subject = $title;
                $title   = grapheme_substr($title, grapheme_strlen($prefix) + 2);
                $body    = Yii::t('export', 'mail_amendment_x_to_y');
                $body    = str_replace(['%AMENDMENT%', '%MOTION%'], [$prefix, $item->getMyMotion()->title], $body);
            }
            if (count($item->getInitiators()) > 0) {
                $initiator = $item->getInitiators() [0];
                $email     = $initiator->contactEmail;
                $phone     = $initiator->contactPhone;
                $name      = $initiator->getNameWithOrga();
                $firstName = StringSplitter::first([' '], grapheme_substr($name, 0, 4) == 'Dr. ' ? grapheme_substr($name, 4) : $name);
            } else {
                $email     = '';
                $phone     = '';
                $name      = '';
                $firstName = '';
            }
            if ($item instanceof Motion) {
                $doc->setCell($row, $COL_TITLE, Spreadsheet::TYPE_TEXT, $item->title);
                $fill ([], ['fo:color' => motionColor]);
            }
            $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, $prefix);
            $doc->setCell($row, $COL_INITIATOR, Spreadsheet::TYPE_TEXT, $name);
            $mailbody = str_replace(['%MOTION%', '%NAME%'], [$body, $firstName], Yii::t('export', 'mail_body'));
            $href     = 'mailto:' . $email . '?subject=' . $prefix . ': ' . $title . '&body=' . rawurlencode($mailbody);
            $doc->setCell($row, $COL_EMAIL, Spreadsheet::TYPE_LINK, ['href' => $href, 'text' => $email ?? 'e-mail']);
            if ($phone) {
                $phoneLink = 'tel:' . StringSplitter::first(['//', ','], $phone);
                $doc->setCell($row, $COL_PHONE, Spreadsheet::TYPE_LINK, ['href' => $phoneLink, 'text' => $phone]);
            }
            $viewUrl    = $item instanceof Motion ? UrlHelper::createMotionUrl($item) : UrlHelper::createAmendmentUrl($item);
            $linkParams = ['href' => UrlHelper::absolutizeLink($viewUrl), 'text' => Yii::t('export', 'motion')];
            $doc->setCell($row, $COL_LINK, Spreadsheet::TYPE_LINK, $linkParams);
        } else { // null
            $doc->setCell($row, $COL_PREFIX, Spreadsheet::TYPE_TEXT, Yii::t('export', 'misc'));
            $fill (['fo:background-color' => agendaColor], []);
        }
    }
    $row++;
}

echo $doc->finishAndGetDocument();
