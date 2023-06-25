<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment[] $amendments
 * @var array[] $collisions
 */

use app\components\HTMLTools;
use app\components\Tools;
use yii\helpers\Html;

if (count($collisions) == 0) {
    echo '<div class="content">';
    echo '<div class="alert alert-success">' . Yii::t('amend', 'merge1_no_collisions') . '</div>';
    echo '</div>';
    return;
}

?>
    <div class="content">
        <div class="alert alert-danger"><?= Yii::t('amend', 'merge1_collision_intro') ?></div>
    </div>
<?php

$fixedWidthSections = [];
foreach ($amendments[array_keys($amendments)[0]]->getActiveSections() as $section) {
    if ($section->getSettings()->fixedWidth) {
        $fixedWidthSections[] = $section->sectionId;
    }
}

foreach ($collisions as $amendmentId => $sections) {
    $amendment = $amendments[$amendmentId];
    echo '<h2 class="green">' .
        Html::encode($amendment->getTitle()) .
        HTMLTools::amendmentDiffTooltip($amendment, 'bottom') .
        '</h2>';
    echo '<div class="content">';
    echo '<div class="amendmentBy">' .
        '(' . Yii::t('amend', 'merge1_submitted_by') . ': ' . Html::encode($amendment->getInitiatorsStr()) . ', ' .
        Yii::t('amend', 'merge1_submitted_on') . ': ' . Tools::formatMysqlDate($amendment->getDate()) . ')</div>';
    foreach ($sections as $sectionId => $paragraphs) {
        foreach ($paragraphs as $paragraphNo => $para) {
            echo '<section class="amendmentOverrideBlock">';
            if ($para['lineFrom'] == $para['lineTo']) {
                $tpl = Yii::t('amend', 'merge1_changein_1');
            } else {
                $tpl = Yii::t('amend', 'merge1_changein_x');
            }
            echo '<h3>' . str_replace(['%LINEFROM%', '%LINETO%'], [$para['lineFrom'], $para['lineTo']], $tpl) . '</h3>';

            $classes = 'text motionTextFormattings ' . (in_array($sectionId, $fixedWidthSections) ? ' fixedWidthFont' : '');

            echo '<label>' . Yii::t('amend', 'merge1_manual_changes') . '</label>';
            echo '<div class="motionTextHolder"><div class="paragraph"><div class="' . $classes . '">' .
                $para['motionNewDiff'] . '</div></div></div>';

            echo '<label>' . str_replace('%AMEND%', $amendment->getFormattedTitlePrefix(), Yii::t('amend', 'merge1_manual_amend')) .
                '</label>';
            echo '<div class="motionTextHolder"><div class="paragraph"><div class="' . $classes . '">' .
                $para['amendmentDiff'] . '</div></div></div>';

            echo '<label>' . str_replace('%AMEND%', $amendment->getFormattedTitlePrefix(), Yii::t('amend', 'merge1_manual_new')) .
                '</label>';
            echo '<textarea name="amendmentOverride[' . $amendmentId . '][' . $sectionId . '][' . $paragraphNo . ']" ';
            echo 'value="" class=""></textarea>';
            echo '<div id="amendmentOverride_' . $amendmentId . '_' . $sectionId . '_' . $paragraphNo . '" class="';
            if (in_array($sectionId, $fixedWidthSections)) {
                echo 'fixedWidthFont ';
            }
            echo 'motionTextFormattings texteditor texteditorBox" title="' . Yii::t('amend', 'merge1_modify_title') . '">';
            echo $para['text'];
            echo '</div></section>';
        }
    }
    echo '</div></div>';
}
