<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var Amendment $amendment
 * @var \app\models\forms\AmendmentProposedChangeForm $form
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;

$this->title        = 'Verfahrensvorschlag';
$layout->fullWidth  = true;
$layout->fullScreen = true;
$layout->addAMDModule('backend/AmendmentEditProposedChange');

$motionUrl = UrlHelper::createMotionUrl($amendment->getMyMotion());
$layout->addBreadcrumb($amendment->getMyMotion()->getBreadcrumbTitle(), $motionUrl);
if (!$consultation->getSettings()->hideTitlePrefix && $amendment->titlePrefix != '') {
    $layout->addBreadcrumb($amendment->titlePrefix, UrlHelper::createAmendmentUrl($amendment));
} else {
    $layout->addBreadcrumb(\Yii::t('amend', 'amendment'), UrlHelper::createAmendmentUrl($amendment));
}
$layout->addBreadcrumb('Verfahrensvorschlag');

echo '<h1>' . 'Verfahrensvorschlag bearbeiten' . '</h1>';

?>
    <div class="content">
        <?php foreach ($form->getProposalSections() as $section) {
            echo $section->data;
        } ?>

    </div>
<?php
