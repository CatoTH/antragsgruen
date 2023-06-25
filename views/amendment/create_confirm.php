<?php

use app\components\UrlHelper;
use app\models\db\{Amendment, AmendmentSection};
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Amendment $amendment
 * @var string $mode
 * @var \app\controllers\Base $controller
 * @var string|null $deleteDraftId
 */

$controller = $this->context;
$layout     = $controller->layoutParams;
$motion     = $amendment->getMyMotion();

$this->title = Yii::t('amend', $mode == 'create' ? 'amendment_create' : 'amendment_edit');

$layout->robotsNoindex = true;
if (!$motion->getMyMotionType()->amendmentsOnly) {
    $layout->addBreadcrumb($motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($motion));
    if ($amendment->amendingAmendmentId) {
        $amendedAmendment = $amendment->amendedAmendment;
        $layout->addBreadcrumb($amendedAmendment->getFormattedTitlePrefix(), UrlHelper::createAmendmentUrl($amendedAmendment));
    }
    $layout->addBreadcrumb(Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment, 'edit'));
} else {
    $layout->addBreadcrumb($motion->getMyMotionType()->titleSingular, UrlHelper::createAmendmentUrl($amendment, 'edit'));
}
$layout->addBreadcrumb(Yii::t('amend', 'confirm'));

echo '<h1>' . Yii::t('amend', 'confirm_amendment') . '</h1>';

?>
    <div class="content">
        <div class="alert alert-info">
            <p><?= Yii::t('amend', 'confirm_hint') ?></p>
        </div>
    </div>
<?php

if ($amendment->changeEditorial !== '') {
    echo '<section id="section_editorial" class="motionTextHolder">';
    echo '<h3 class="green">' . Yii::t('amend', 'editorial_hint') . '</h3>';
    echo '<div class="paragraph"><div class="text motionTextFormattings">';
    echo $amendment->changeEditorial;
    echo '</div></div></section>';
}

/** @var AmendmentSection[] $sections */
$sections = $amendment->getSortedSections(false);
foreach ($sections as $section) {
    echo $section->getSectionType()->getAmendmentFormatted();
}


if ($amendment->changeExplanation !== '') {
    echo '<div class="motionTextHolder amendmentReasonHolder">';
    echo '<h3 class="green">' . Yii::t('amend', 'reason') . '</h3>';
    echo '<div class="paragraph"><div class="text">';
    echo $amendment->changeExplanation;
    echo '</div></div>';
    echo '</div>';
}


echo '<div class="motionTextHolder">
        <h3 class="green">' . Yii::t('amend', 'initiators_title') . '</h3>
        <div>';

if (count($amendment->getSupporters(true)) + count($amendment->getInitiators()) > 1) {
    echo '<ul>';
} else {
    echo '<ul class="onlyOneSupporter">';
}
foreach ($amendment->getInitiators() as $initiator) {
    echo '<li>';
    echo '<strong>' . $initiator->getNameWithResolutionDate(true) . '</strong>';
    if ($initiator->personType === \app\models\db\ISupporter::PERSON_ORGANIZATION) {
        $data = [];
        if ($initiator->contactName) {
            $data[] = Html::encode($initiator->contactName);
        }
        if ($initiator->contactEmail) {
            $data[] = Html::encode($initiator->contactEmail);
        }
        if ($initiator->contactPhone) {
            $data[] = Html::encode($initiator->contactPhone);
        }
        if (count($data) > 0) {
            echo '<br><br>' . Yii::t('initiator', 'orgaContactName') . ':<br>';
            echo implode("<br>", $data);
        }
    }
    echo '</li>';
}

foreach ($amendment->getSupporters(true) as $unt) {
    echo '<li>' . $unt->getNameWithResolutionDate(true) . '</li>';
}
echo '
            </ul>
        </div>
    </div>';

echo Html::beginForm('', 'post', ['id' => 'amendmentConfirmForm']);

?>
    <div class="content saveCancelRow">
        <div class="saveCol">
            <button type="submit" name="confirm" class="btn btn-success">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <?= $amendment->getSubmitButtonLabel() ?>
            </button>
        </div>
        <div class="cancelCol">
            <button type="submit" name="modify" class="btn">
                <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
                <?= Yii::t('amend', 'button_correct') ?>
            </button>
        </div>
    </div>
<?php
echo Html::endForm();

if ($deleteDraftId) {
    $controller->layoutParams->addOnLoadJS('localStorage.removeItem(' . json_encode($deleteDraftId) . ');');
}
