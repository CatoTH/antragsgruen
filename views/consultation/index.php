<?php

use \app\components\Tools;
use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var $this yii\web\View
 * @var \app\models\db\Consultation $consultation
 * @var string $introText
 * @var Motion[] $motions
 * @var \app\models\db\User|null $myself
 * @var \app\models\db\MotionSupporter[] $myMotions
 * @var \app\models\db\AmendmentSupporter[] $myAmendments
 */

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$wording    = $consultation->getWording();
$layout     = $controller->layoutParams;

$this->title                = $consultation->title . ' (Antragsgrün)';
$layout->breadcrumbsTopname = ($consultation->titleShort ? $consultation->titleShort : $consultation->title);
$layout->breadcrumbs        = array();


//include(__DIR__ . "/sidebar.php");

echo '<h1>';

echo Html::encode($consultation->title);
if ($consultation->eventDateFrom != "" && $consultation->eventDateFrom != "0000-00-00") {
    if ($consultation->eventDateFrom != $consultation->eventDateTo) {
        echo ", " . Tools::formatMysqlDate($consultation->eventDateFrom);
        echo " - " . Tools::formatMysqlDate($consultation->eventDateTo);
    } else {
        echo ", " . Tools::formatMysqlDate($consultation->eventDateFrom);
    }

}
//$editlink = $einleitungstext->getEditLink();
//if ($editlink !== null) echo "<a style='font-size: 10px;' href='" .
//CHtml::encode($this->createUrl($editlink[0], $editlink[1])) . "'>Bearbeiten</a>";
echo '</h1>';

echo '<div class="content" style="overflow: auto;">';

if ($consultation->deadlineMotions != "") {
    echo '<p class="antragsschluss_kreis">Antrags&shy;schluss: ';
    echo Tools::formatMysqlDateTime($consultation->deadlineMotions) . "</p>\n";
}

//echo $einleitungstext->getHTMLText();
echo $introText;

echo '</div>';

require(__DIR__ . DIRECTORY_SEPARATOR . $consultation->getSettings()->getStartLayoutView() . '.php');

if ($myself) {
    if (count($myMotions)) {
        echo '<h3>' . $wording->get("Meine Anträge") . '</h3>';
        echo '<div class="content"><ul class="antragsliste">';

        foreach ($myMotions as $motionSupport) {
            $motion = $motionSupport->motion;
            echo "<li>";
            if ($motion->status == Motion::STATUS_WITHDRAWN) {
                echo "<span style='text-decoration: line-through;'>";
            }
            $motionLink = URL::toRoute(['motion/show', 'motionId' => $motion->id]);
            echo Html::a($motion->title, $motionLink);
            if ($motionSupport->role == MotionSupporter::ROLE_INITIATOR) {
                echo " (InitiatorIn)";
            }
            if ($motionSupport->role == MotionSupporter::ROLE_SUPPORTER) {
                echo " (UnterstützerIn)";
            }
            if ($motion->status == Motion::STATUS_WITHDRAWN) {
                echo "</span>";
            }
            echo "</li>\n";
        }
        echo '</ul></div>';
    }

    if (count($myAmendments) > 0) {
        echo '<h3>' . $wording->get("Meine Änderungsanträge") . '</h3>';
        echo '<div class="content"><ul class="antragsliste">';
        foreach ($myAmendments as $amendmentSupport) {
            $amendment = $amendmentSupport->amendment;
            echo "<li>";
            if ($amendment->status == Amendment::STATUS_WITHDRAWN) {
                echo "<span style='text-decoration: line-through;'>";
            }
            $amendmentUrl = Url::toRoute(
                [
                    'amendment/show',
                    'motionId'    => $amendment->motionId,
                    'amendmentId' => $amendment->id
                ]
            );
            echo Html::a(
                Html::encode($amendment->titlePrefix . " zu " . $amendment->motion->titlePrefix),
                $amendmentUrl
            );
            if ($amendmentSupport->role == AmendmentSupporter::ROLE_INITIATOR) {
                echo " (InitiatorIn)";
            }
            if ($amendmentSupport->role == AmendmentSupporter::ROLE_SUPPORTER) {
                echo " (UnterstützerIn)";
            }
            if ($amendment->status == Amendment::STATUS_WITHDRAWN) {
                echo "</span>";
            }
            echo "</li>\n";
        }
        echo '</ul></div>';
    }
}
