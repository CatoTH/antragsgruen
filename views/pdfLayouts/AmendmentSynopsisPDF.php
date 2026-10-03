<?php

declare(strict_types=1);

namespace app\views\pdfLayouts;

use yii\helpers\Html;

/**
 * Landscape PDF showing the motion text on the left and the changes of all amendments on the right side.
 * This export does not use the configurable PDF layouts, as their headers are designed for portrait pages.
 */
class AmendmentSynopsisPDF extends IPdfWriter
{
    public const MARGIN_LEFT = 10;
    public const MARGIN_RIGHT = 10;
    public const MARGIN_TOP = 20;
    public const MARGIN_BOTTOM = 15;
    public const COLUMN_GAP = 8;
    public const LINE_NUMBER_WIDTH = 8;

    private string $headerTitle = '';

    public function __construct()
    {
        parent::__construct('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $this->setCellHeightRatio(1.5);
        $this->SetMargins(self::MARGIN_LEFT, self::MARGIN_TOP, self::MARGIN_RIGHT);
        $this->SetAutoPageBreak(true, self::MARGIN_BOTTOM);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->SetFont('helvetica', '', 10);

        $this->setHtmlVSpace([
            'ul'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'li'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'ol'         => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'div'        => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'p'          => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'blockquote' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
        ]);
    }

    public function setHeaderTitle(string $title): void
    {
        $this->headerTitle = $title;
    }

    public function getColumnWidth(): float
    {
        return ($this->getPageWidth() - self::MARGIN_LEFT - self::MARGIN_RIGHT - self::COLUMN_GAP) / 2;
    }

    public function getLeftColumnX(): float
    {
        return self::MARGIN_LEFT;
    }

    public function getRightColumnX(): float
    {
        return self::MARGIN_LEFT + $this->getColumnWidth() + self::COLUMN_GAP;
    }

    public function getContentWidth(): float
    {
        return $this->getPageWidth() - self::MARGIN_LEFT - self::MARGIN_RIGHT;
    }

    /**
     * The motion text uses forced line breaks, so the font needs to be scaled down so that one line still fits
     * into the (narrower) column. The regular portrait PDF prints the text into a 173mm wide cell.
     */
    public function getScaledFontSize(float $regularFontSize): float
    {
        $textWidth = $this->getColumnWidth() - self::LINE_NUMBER_WIDTH;

        return floor($regularFontSize * $textWidth / 173 * 2) / 2;
    }

    public function Header(): void
    {
        if ($this->headerTitle === '') {
            return;
        }

        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(0, 0, 0);
        $this->setCellHeightRatio(1.25);
        $this->writeHTMLCell(
            $this->getContentWidth(),
            8,
            self::MARGIN_LEFT,
            6,
            Html::encode($this->headerTitle),
            ['B' => ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [0, 0, 0]]],
            1,
            false,
            true,
            'C'
        );
    }

    public function Footer(): void
    {
        $this->SetY(-12);
        $this->SetFont('helvetica', '', 9);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(
            $this->getContentWidth(),
            8,
            \Yii::t('export', 'Page') . ' ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(),
            0,
            0,
            'R',
            false,
            '',
            0,
            false,
            'T',
            'M'
        );
    }
}
