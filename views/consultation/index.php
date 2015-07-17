<?php

use \app\components\Tools;
use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 * @var Motion[] $motions
 * @var string $saveUrl
 * @var \app\models\db\User|null $myself
 * @var \app\models\db\MotionSupporter[] $myMotions
 * @var \app\models\db\AmendmentSupporter[] $myAmendments
 * @var bool $admin
 */

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title = $consultation->title . ' (Antragsgrün)';


if ($admin) {
    $layout->loadCKEditor();
}

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
echo '</h1>';

echo '<div class="content contentPage contentPageWelcome" style="overflow: auto;">';

if (count($consultation->motionTypes) == 1 && $consultation->motionTypes[0]->deadlineMotions != "") {
    echo '<p class="deadlineCircle">Antrags&shy;schluss: ';
    echo Tools::formatMysqlDateTime($consultation->motionTypes[0]->deadlineMotions) . "</p>\n";
}

if ($admin) {
    echo '<a href="#" class="editCaller" style="float: right;">Bearbeiten</a><br>';
    echo Html::beginForm($saveUrl, 'post');
}

$pageData = \app\components\MessageSource::getPageData($consultation, 'welcome');
echo '<article class="textHolder" id="stdTextHolder">';
echo $pageData->text;
echo '</article>';

if ($admin) {
    echo '<div class="textSaver" style="display: none;">';
    echo '<button class="btn btn-primary" type="button" data-save-url="' . Html::encode($saveUrl) . '">';
    echo 'Speichern</button></div>';

    echo Html::endForm();
    $layout->addOnLoadJS('$.Antragsgruen.contentPageEdit();');
}

echo '</div>';

echo $controller->showErrors();

require(__DIR__ . DIRECTORY_SEPARATOR . $consultation->getSettings()->getStartLayoutView() . '.php');

if ($myself) {
    if (count($myMotions)) {
        echo '<h3 class="green">' . Yii::t('con', 'Meine Anträge') . '</h3>';
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
        echo '<h3 class="green">' . Yii::t('con', 'Meine Änderungsanträge') . '</h3>';
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
