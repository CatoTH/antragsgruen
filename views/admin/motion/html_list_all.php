<?php

use app\models\db\Amendment;
use app\models\db\ConsultationAgendaItem;
use app\models\db\Motion;
use app\models\sectionTypes\Title;
use yii\helpers\Html;

/**
 * @var $this yii\web\View
 * @var array $items
 */

const agendaColor = '#fff2cc';
const maxLength = 666;

echo '<html><head><meta charset="UTF-8">';
echo '<style>table{border-collapse: collapse; text-align: left;} th,td{border: 1px solid black;} tr.item{background-color:' . agendaColor . ';}</style>';
echo '</head><body><table>';

echo '<tr>';
echo '<th>Kürzel</th>';
echo '<th>Antragsteller*in</th>';
echo '<th>Titel</th>';
echo '<th>Antragstext</th>';
echo '</tr>';

foreach ($items as $item) {
    if ($item instanceof ConsultationAgendaItem) {
        echo '<tr style="background-color:' . agendaColor . '">';
        echo '<td colspan="4">' . $item->code . '&nbsp;&nbsp;&nbsp;&nbsp;' . $item->title . '</span></td>';
        echo '</tr>';
    }
    else if ($item instanceof Motion || $item instanceof Amendment) {
        $title = $item->title;
        $prefix = $item->titlePrefix;
        $initiators = implode (', ',array_map (function ($initiator) {return $initiator->getNameWithOrga ();},$item->getInitiators()));

        echo '<tr>';
        echo '<td>' . Html::encode ($prefix) . '</td>';
        echo '<td>' . Html::encode ($initiators) . '</td>';
        echo '<td>';
        if ($item instanceof Motion)
            echo Html::encode ($item->title);
        echo '</td>';
        echo '<td>';
        if ($item instanceof Amendment) {
            foreach ($item->getSortedSections(false) as $section) {
                $block = $section->getSectionType()->getAmendmentHTMLTextBlocks() [1];
                if (strlen ($block) < maxLength)
                    echo $block;
            }
        }
        echo '</td>';
        echo '</tr>';
    }
    else { // null
        echo '<tr class="item">';
        echo '<td colspan="4">Sonstiges</td>';
        echo '</tr>';
    }
}

echo '</table></body></html>';
