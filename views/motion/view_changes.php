<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;
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
    <div class="content">
        <?php
        echo $controller->showErrors();

        ?>
    </div>
<?php

foreach ($changes as $change) {
    echo '<section class="motionChangeView section' . $change->getSectionId() . '">';
    echo '<h2 class="green">' . Html::encode($change->getSectionTitle()) . '</h2>';
    if (!$change->hasChanges()) {
        echo '<div class="content noChanges">';
        echo \Yii::t('motion', 'diff_no_change');
        echo '</div>';
        continue;
    }

    switch ($change->getSectionTypeId()) {
        case ISectionType::TYPE_TEXT_SIMPLE:
            $firstLine  = $change->getFirstLineNumber();
            $diffGroups = $change->getSimpleTextDiffGroups();
            echo '<div class="motionTextHolder"><div class="paragraph lineNumbers">';

            $wrapStart = '<section class="paragraph"><div class="text motionTextFormattings';
            if ($change->isFixedWithFont()) {
                $wrapStart .= ' fixedWidthFont';
            }
            $wrapStart .= '">';
            $wrapEnd   = '</div></section>';
            echo TextSimple::formatDiffGroup($diffGroups, $wrapStart, $wrapEnd, $firstLine);

            echo '</div></div>';
            break;
        default:
            echo '<div class="content notDisplayable">';
            echo \Yii::t('motion', 'diff_err_display');
            echo '</div>';
    }
    echo '</section>';
}