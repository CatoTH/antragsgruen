<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\settings\MotionSection;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $motion
 * @var string $mode
 * @var \app\controllers\Base $controller
 * @var string|null $deleteDraftId
 */

$controller = $this->context;

$typeName = $motion->getMyMotionType()->titleSingular;
if ($mode === 'create') {
    $this->title = $typeName;
} else {
    $this->title = str_replace('%TYPE%', $typeName, Yii::t('motion', 'motion_edit'));
}

$controller->layoutParams->robotsNoindex = true;
$controller->layoutParams->addBreadcrumb($this->title);
$controller->layoutParams->addBreadcrumb(Yii::t('motion', 'confirm_bread'));
$controller->layoutParams->bodyCssClasses[] = 'createConfirmPage';

echo '<h1>' . Yii::t('motion', 'Confirm Motion') . ': ' . Html::encode($motion->getTitleWithIntro()) . '</h1>';

?>
    <section class="toolbarBelowTitle versionSwitchtoolbar" data-antragsgruen-widget="frontend/MotionCreateConfirm">
        <div class="styleSwitcher">
            <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-default active">
                    <input type="radio" name="viewMode" value="web" autocomplete="off" checked>
                    <?= Yii::t('motion', 'confirm_view_web') ?>
                </label>
                <label class="btn btn-default">
                    <input type="radio" name="viewMode" value="pdf" autocomplete="off">
                    <?= Yii::t('motion', 'confirm_view_pdf') ?>
                </label>
            </div>
        </div>
    </section>

    <div class="content">
        <div class="alert alert-info">
            <p><?= Yii::t('motion', 'confirm_hint') ?></p>
        </div>
    </div>

<?php
$pdfUrl = UrlHelper::createMotionUrl($motion, 'pdf', ['showAlways' => $motion->getShowAlwaysToken()]);
$iframeUrl = UrlHelper::createMotionUrl($motion, 'embeddedpdf', ['file' => $pdfUrl]);
$iframe = '<iframe class="pdfViewer" src="' . Html::encode($iframeUrl) . '"></iframe>';
?>
    <section class="pdfVersion" data-src="<?= Html::encode($iframe) ?>"></section>
<?php

$main = $right = '';
foreach ($motion->getSortedSections(true, true) as $section) {
    if ($section->getSectionType()->isEmpty()) {
        continue;
    }

    if ($section->isLayoutRight()) {
        $right .= '<section class="sectionType' . $section->getSettings()->type . '">';
        $right .= $section->getSectionType()->getSimple(true, true);
        $right .= '</section>';
    } else {
        $settings = $section->getSettings();
        $main .= '<section class="motionTextHolder sectionType' . $section->getSettings()->type . '">';
        if ($settings->type !== \app\models\sectionTypes\ISectionType::TYPE_PDF_ATTACHMENT &&
            $settings->type !== \app\models\sectionTypes\ISectionType::TYPE_IMAGE
        ) {
            $main .= '<h3 class="green">' . Html::encode($settings->title) . '</h3>';
        }
        $main .= '<div class="consolidated">';

        if ($settings->getSettingsObj()->public === MotionSection::PUBLIC_NO) {
            $main .= '<div class="alert alert-info alertNonPublicSection"><p>' . Yii::t('motion', 'field_unpublic') . '</p></div>';
        }
        $main .= $section->getSectionType()->getSimple(false, true);

        $main .= '</div>';
        $main .= '</section>';
    }
}

if ($right === '') {
    echo $main;
} else {
    ?>
    <div class="webVersion motionTwoCols">
        <div class="motionMainCol">
            <?= $main ?>
        </div>
        <div class="motionRightCol">
            <?= $right ?>
        </div>
    </div>
    <?php
}

if (count($motion->getSupporters(true)) + count($motion->getInitiators()) > 0) {
    ?>
    <div class="webVersion motionTextHolder">
        <h3 class="green"><?= Yii::t('motion', 'initiators_head') ?></h3>
        <div class="paragraph">
            <ul <?= (count($motion->getSupporters(true)) + count($motion->getInitiators()) <= 1 ? 'class="onlyOneSupporter"' : '') ?>>
                <?php
                foreach ($motion->getInitiators() as $initiator) {
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

                foreach ($motion->getSupporters(true) as $unt) {
                    echo '<li>' . $unt->getNameWithResolutionDate(true) . '</li>';
                }
                ?>
            </ul>
        </div>
    </div>
    <?php
}

echo Html::beginForm('', 'post', ['id' => 'motionConfirmForm']);
?>
    <div class="content saveCancelRow">
        <div class="saveCol">
            <button type="submit" name="confirm" class="btn btn-success">
                <span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
                <?= $motion->getSubmitButtonLabel() ?>
            </button>
        </div>
        <div class="cancelCol">
            <button type="submit" name="modify" class="btn">
                <span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
                <?= Yii::t('motion', 'button_correct') ?>
            </button>
        </div>
    </div>

<?php
echo Html::endForm();

if ($deleteDraftId) {
    $controller->layoutParams->addOnLoadJS('localStorage.removeItem(' . json_encode($deleteDraftId) . ');');
}
