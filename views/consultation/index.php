<?php

use app\components\Tools;
use app\models\db\Amendment;
use app\models\db\AmendmentSupporter;
use app\models\db\ConsultationMotionType;
use app\models\db\Motion;
use app\models\db\MotionSupporter;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 * @var Motion[] $motions
 * @var \app\models\db\User|null $myself
 * @var \app\models\db\MotionSupporter[] $myMotions
 * @var \app\models\db\AmendmentSupporter[] $myAmendments
 * @var bool $admin
 */

/** @var \app\controllers\ConsultationController $controller */
$controller               = $this->context;
$layout                   = $controller->layoutParams;
$layout->bodyCssClasses[] = 'consultationIndex';
$this->title              = $consultation->title;


if ($admin) {
    $layout->loadCKEditor();
}

echo '<h1>';

echo Html::encode($consultation->title);
if ($consultation->eventDateFrom != '' && $consultation->eventDateFrom != '0000-00-00') {
    if ($consultation->eventDateFrom != $consultation->eventDateTo) {
        echo ', ' . Tools::formatMysqlDate($consultation->eventDateFrom);
        echo ' - ' . Tools::formatMysqlDate($consultation->eventDateTo);
    } else {
        echo ', ' . Tools::formatMysqlDate($consultation->eventDateFrom);
    }
}
echo '</h1>';

echo $layout->getMiniMenu('sidebarSmall');

echo '<div class="content contentPage contentPageWelcome" style="overflow: auto;">';

if (count($consultation->motionTypes) === 1) {
    $deadline = $consultation->motionTypes[0]->getUpcomingDeadline(ConsultationMotionType::DEADLINE_MOTIONS);
    if ($deadline) {
        echo '<p class="deadlineCircle">' . \Yii::t('con', 'deadline_circle') . ': ';
        echo Tools::formatMysqlDateTime($deadline) . "</p>\n";
    }
}

$pageData = \app\models\db\ConsultationText::getPageData($consultation->site, $consultation, 'welcome');
$saveUrl  = $pageData->getSaveUrl();
if ($admin) {
    echo Html::beginForm($saveUrl, 'post', [
        'data-upload-url'          => $pageData->getUploadUrl(),
        'data-image-browse-url'    => $pageData->getImageBrowseUrl(),
        'data-antragsgruen-widget' => 'frontend/ContentPageEdit',
    ]);
    echo '<a href="#" class="editCaller" style="float: right;">' . Yii::t('base', 'edit') . '</a><br>';
}

echo '<article class="textHolder" id="stdTextHolder">';
echo $pageData->text;
echo '</article>';

if ($admin) {
    echo '<div class="textSaver hidden">';
    echo '<button class="btn btn-primary" type="submit">';
    echo Yii::t('base', 'save') . '</button></div>';

    echo Html::endForm();
}

echo '</div>';

echo $this->render('_index_phases_progress', ['consultation' => $consultation]);

echo $controller->showErrors();

if ($myself) {
    if (count($myMotions)) {
        echo '<h3 class="green">' . Yii::t('con', 'My Motions') . '</h3>';
        echo '<div class="content myMotionList"><ul>';

        foreach ($myMotions as $motionSupport) {
            $motion = $motionSupport->motion;
            echo '<li>';
            if ($motion->status === Motion::STATUS_WITHDRAWN) {
                echo "<span style='text-decoration: line-through;'>";
            }
            $motionLink = \app\components\UrlHelper::createMotionUrl($motion);
            echo Html::a(Html::encode($motion->getTitleWithPrefix()), $motionLink, ['class' => 'motion' . $motion->id]);
            if ($motionSupport->role === MotionSupporter::ROLE_INITIATOR) {
                echo ' (' . Yii::t('motion', 'Initiator') . ')';
            }
            if ($motionSupport->role === MotionSupporter::ROLE_SUPPORTER) {
                echo ' (' . Yii::t('motion', 'Supporter') . ')';
            }
            echo ': ' . Html::encode($motion->getStatusNames()[$motion->status]);
            if ($motion->status == Motion::STATUS_WITHDRAWN) {
                echo '</span>';
            }
            if ($motion->status === Motion::STATUS_COLLECTING_SUPPORTERS) {
                echo '<div>' . \Yii::t('motion', 'support_collect_status') . ': ';
                echo count($motion->getSupporters());
                echo ' <small>(' . \Yii::t('motion', 'support_collect_min') . ': ';
                echo $motion->motionType->getMotionSupportTypeClass()->getSettingsObj()->minSupporters;
                echo ')</small></div>';
            }
            echo "</li>\n";
        }
        echo '</ul></div>';
    }

    if (count($myAmendments) > 0) {
        echo '<h3 class="green">' . Yii::t('con', 'My Amendments') . '</h3>';
        echo '<div class="content myAmendmentList"><ul>';
        foreach ($myAmendments as $amendmentSupport) {
            $amendment = $amendmentSupport->amendment;
            echo '<li>';
            if ($amendment->status === Amendment::STATUS_WITHDRAWN) {
                echo "<span style='text-decoration: line-through;'>";
            }
            $amendmentUrl = \app\components\UrlHelper::createAmendmentUrl($amendment);
            echo Html::a(
                Html::encode($amendment->getTitle()),
                $amendmentUrl,
                ['class' => 'amendment' . $amendment->id]
            );
            if ($amendmentSupport->role === AmendmentSupporter::ROLE_INITIATOR) {
                echo ' (' . Yii::t('amend', 'initiator') . ')';
            }
            if ($amendmentSupport->role === AmendmentSupporter::ROLE_SUPPORTER) {
                echo ' (' . Yii::t('amend', 'supporter') . ')';
            }
            echo ': ' . Html::encode($amendment->getStatusNames()[$amendment->status]);
            if ($amendment->status === Amendment::STATUS_WITHDRAWN) {
                echo '</span>';
            }
            if ($amendment->status === Amendment::STATUS_COLLECTING_SUPPORTERS) {
                echo '<div>' . \Yii::t('motion', 'support_collect_status') . ': ';
                echo count($amendment->getSupporters());
                echo ' <small>(' . \Yii::t('motion', 'support_collect_min') . ': ';
                echo $amendment->getMyMotionType()->getAmendmentSupportTypeClass()->getSettingsObj()->minSupporters;
                echo ')</small></div>';
            }
            echo '</li>';
        }
        echo '</ul></div>';
    }
}

echo $this->render($consultation->getSettings()->getStartLayoutView(), [
    'consultation' => $consultation,
    'layout'       => $layout,
    'admin'        => $admin,
]);
