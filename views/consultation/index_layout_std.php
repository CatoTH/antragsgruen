<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var array $motions
 * @var Consultation $consultation
 */

$hasPDF = $consultation->getSettings()->hasPDF;

foreach ($motions as $name => $motns) {
    echo "<ul class='motionList'>";
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
            echo Html::a("PDF", UrlHelper::createMotionUrl($motion, 'pdf'), ['class' => 'pdfLink']);
        }
        echo "</p>\n";
        echo '<p class="info">von ' . Html::encode($motion->getInitiatorsStr()) . '</p>';

        if (count($motion->amendments) > 0) {
            echo "<ul class='amendments'>";
            $aes = $motion->getSortedAmendments();
            foreach ($aes as $ae) {
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
