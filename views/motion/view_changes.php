<?php

use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var Motion $newMotion
 * @var Motion $oldMotion
 * @var \app\models\MotionSectionChanges[] $changes
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout     = $controller->layoutParams;
if (!$newMotion->getMyConsultation()->getForcedMotion()) {
    $layout->addBreadcrumb($newMotion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($newMotion));
}
$layout->addBreadcrumb(\Yii::t('motion', 'diff_bc'));

$this->title = str_replace(
    ['%FROM%', '%TO%'],
    [$oldMotion->titlePrefix, $newMotion->titlePrefix],
    \Yii::t('motion', 'diff_title')
);
?>
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="motionChangeView content">
        <?php
        echo $controller->showErrors();
        $oldLink = UrlHelper::createMotionUrl($oldMotion);
        $newLink = UrlHelper::createMotionUrl($newMotion);
        ?>
        <table class="motionDataTable">
            <tr>
                <th><?= \Yii::t('motion', 'diff_old_version') ?>:</th>
                <td><?= Html::a(Html::encode($oldMotion->titlePrefix), $oldLink) ?></td>
            </tr>
            <tr>
                <th><?= \Yii::t('motion', 'status') ?>:</th>
                <td><?= $oldMotion->getFormattedStatus() ?></td>
            </tr>
            <tr>
                <th><?= \Yii::t('motion', ($oldMotion->isSubmitted() ? 'submitted_on' : 'created_on')) ?>:</th>
                <td><?= Tools::formatMysqlDateTime($oldMotion->dateCreation, null, false) ?></td>
            </tr>
        </table>

        <table class="motionDataTable">
            <tr>
                <th><?= \Yii::t('motion', 'diff_new_version') ?>:</th>
                <td><?= Html::a(Html::encode($newMotion->titlePrefix), $newLink) ?></td>
            </tr>
            <tr>
                <th><?= \Yii::t('motion', 'status') ?>:</th>
                <td><?= $newMotion->getFormattedStatus() ?></td>
            </tr>
            <tr>
                <th><?= \Yii::t('motion', ($newMotion->isSubmitted() ? 'submitted_on' : 'created_on')) ?>:</th>
                <td><?= Tools::formatMysqlDateTime($newMotion->dateCreation, null, false) ?></td>
            </tr>
        </table>
    </div>
<?php

foreach ($changes as $change) {
    echo '<section class="motionChangeView section' . $change->getSectionId() . '">';
    echo '<h2 class="green">' . Html::encode($change->getSectionTitle()) . '</h2>';
    echo $this->render('_view_change_section', ['change' => $change]);
    echo '</section>';
}
