<?php

use app\components\MotionSorter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Consultation $consultation
 */

$hasPDF = $consultation->getSettings()->hasPDF;

$motions = MotionSorter::getSortedMotions($consultation, $consultation->motions);
foreach ($motions as $name => $motns) {
    echo "<ul class='motionListStd'>";
    foreach ($motns as $motion) {
        /** @var Motion $motion */
        $classes = array('motion');
        if ($motion->motionType->cssicon != '') {
            $classes[] = $motion->motionType->cssicon;
        }

        if ($motion->status == Motion::STATUS_WITHDRAWN) {
            $classes[] = 'withdrawn';
        }
        echo '<li class="' . implode(' ', $classes) . '">';
        echo "<p class='date'>" . Tools::formatMysqlDate($motion->dateCreation) . "</p>\n";
        echo "<p class='title'>\n";
        echo Html::a($motion->getTitleWithPrefix(), UrlHelper::createMotionUrl($motion));
        if ($hasPDF) {
            $html = '<span class="glyphicon glyphicon-download-alt"></span> PDF';
            echo Html::a($html, UrlHelper::createMotionUrl($motion, 'pdf'), ['class' => 'pdfLink']);
        }
        echo "</p>\n";
        echo '<p class="info">von ' . Html::encode($motion->getInitiatorsStr()) . '</p>';

        $amendments = MotionSorter::getSortedAmendments($consultation, $motion->amendments);
        if (count($amendments) > 0) {
            echo "<ul class='amendments'>";
            foreach ($amendments as $ae) {
                echo "<li" . ($ae->status == Amendment::STATUS_WITHDRAWN ? " class='withdrawn'" : "") . ">";
                echo "<span class='date'>" . Tools::formatMysqlDate($ae->dateCreation) . "</span>\n";
                $name = (trim($ae->titlePrefix) == "" ? "-" : $ae->titlePrefix);
                echo Html::a($name, UrlHelper::createAmendmentUrl($ae));
                echo "<span class='info'>" . Html::encode($ae->getInitiatorsStr()) . "</span>\n";
                echo "</li>\n";
            }
            echo "</ul>";
        }
        echo "</li>\n";
    }
    echo "</ul>";
}
