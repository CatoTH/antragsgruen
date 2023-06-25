<?php

use app\models\sectionTypes\ISectionType;
use app\components\{Tools, UrlHelper};
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
$layout->addBreadcrumb(Yii::t('motion', 'diff_bc'));

$this->title = str_replace(
    ['%FROM%', '%TO%'],
    [$oldMotion->getFormattedTitlePrefix(), $newMotion->getFormattedTitlePrefix()],
    Yii::t('motion', 'diff_title')
);
?>
    <h1><?= Html::encode($this->title) ?></h1>
    <div class="motionChangeView content">
        <?php
        echo $controller->showErrors();
        $oldLink = UrlHelper::createMotionUrl($oldMotion);
        $newLink = UrlHelper::createMotionUrl($newMotion);
        $oldTitle = $oldMotion->getFormattedTitlePrefix();
        $newTitle = $newMotion->getFormattedTitlePrefix();
        if ($oldMotion->version !== Motion::VERSION_DEFAULT || $newMotion->version !== Motion::VERSION_DEFAULT) {
            $oldTitle .= ' (' . $oldMotion->getFormattedVersion() . ')';
            $newTitle .= ' (' . $newMotion->getFormattedVersion() . ')';
        }
        ?>
        <table class="motionDataTable">
            <tr>
                <th><?= Yii::t('motion', 'diff_old_version') ?>:</th>
                <td><?= Html::a(Html::encode($oldTitle), $oldLink) ?></td>
            </tr>
            <tr>
                <th><?= Yii::t('motion', 'status') ?>:</th>
                <td><?= $oldMotion->getFormattedStatus() ?></td>
            </tr>
            <tr>
                <th><?= Yii::t('motion', ($oldMotion->isSubmitted() ? 'submitted_on' : 'created_on')) ?>:</th>
                <td><?= Tools::formatMysqlDateTime($oldMotion->dateCreation, false) ?></td>
            </tr>
        </table>

        <table class="motionDataTable">
            <tr>
                <th><?= Yii::t('motion', 'diff_new_version') ?>:</th>
                <td><?= Html::a(Html::encode($newTitle), $newLink) ?></td>
            </tr>
            <tr>
                <th><?= Yii::t('motion', 'status') ?>:</th>
                <td><?= $newMotion->getFormattedStatus() ?></td>
            </tr>
            <tr>
                <th><?= Yii::t('motion', ($newMotion->isSubmitted() ? 'submitted_on' : 'created_on')) ?>:</th>
                <td><?= Tools::formatMysqlDateTime($newMotion->dateCreation, false) ?></td>
            </tr>
        </table>
    </div>
<?php

foreach ($changes as $change) {
    echo $this->render('_view_change_section', ['change' => $change]);
}


$protocol = $newMotion->getProtocol();
if ($protocol && $protocol->status === \app\models\db\IAdminComment::TYPE_PROTOCOL_PUBLIC) {
    ?>
    <section class="protocolHolder section">
        <h2 class="green"><?= Yii::t('motion', 'protocol') ?></h2>
        <div class="content">
            <?= $protocol->text ?>
        </div>
    </section>
<?php
}
