<?php

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Amendment[] $amendments
 * @var array[] $collissions
 */

use app\components\Tools;
use yii\helpers\Html;

if (count($collissions) == 0) {
    echo '<div class="content">';
    echo '<div class="alert alert-success">' . \Yii::t('amend', 'merge1_no_collissions') . '</div>';
    echo '</div>';
    return;
}

?>
    <div class="content">
        <div class="alert alert-danger"><?= \Yii::t('amend', 'merge1_collission_intro') ?></div>
    </div>
<?php

$fixedWidthSections = [];
foreach ($amendments[array_keys($amendments)[0]]->getActiveSections() as $section) {
    if ($section->getSettings()->fixedWidth) {
        $fixedWidthSections[] = $section->sectionId;
    }
}

foreach ($collissions as $amendmentId => $sections) {
    $amendment = $amendments[$amendmentId];
    echo '<h2 class="green">' . Html::encode($amendment->getTitle()) . '</h2>';
    echo '<div class="content">';
    echo '<div class="amendmentBy">' .
        '(' . \Yii::t('amend', 'merge1_submitted_by') . ': ' . $amendment->getInitiatorsStr() . ', ' .
        \Yii::t('amend', 'merge1_submitted_on') . ': ' . Tools::formatMysqlDate($amendment->getDate()) . ')</div>';
    foreach ($sections as $sectionId => $paragraphs) {
        foreach ($paragraphs as $paragraphNo => $para) {
            echo '<section class="amendmentOverrideBlock">';
            if ($para['lineFrom'] == $para['lineTo']) {
                $tpl = \Yii::t('amend', 'merge1_changein_1');
            } else {
                $tpl = \Yii::t('amend', 'merge1_changein_x');
            }
            echo '<label>' . str_replace(['%LINEFROM%', '%LINETO%'], [$para['lineFrom'], $para['lineTo']], $tpl) .
                '</label>';
            echo '<textarea name="amendmentOverride[' . $amendmentId . '][' . $sectionId . '][' . $paragraphNo . ']" ';
            echo 'value="" class=""></textarea>';
            echo '<div id="amendmentOverride_' . $amendmentId . '_' . $sectionId . '_' . $paragraphNo . '" class="';
            if (in_array($sectionId, $fixedWidthSections)) {
                echo 'fixedWidthFont ';
            }
            echo 'texteditor texteditorBox" title="' . \Yii::t('amend', 'merge1_modify_title') . '">';
            echo $para['text'];
            echo '</div></section>';
        }
    }
    echo '</div></div>';
}
