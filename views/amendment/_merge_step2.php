<?php
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var Amendment $amendment
 * @var array $paragraphSections
 * @var bool $needsCollissionCheck
 */

$fixedWidthSections = [];
foreach ($amendment->getActiveSections() as $section) {
    if ($section->getSettings()->fixedWidth) {
        $fixedWidthSections[] = $section->sectionId;
    }
}

?>

<div class="step_2">
    <fieldset class="affectedParagraphs">
        <?php
        foreach ($paragraphSections as $sectionId => $paragraphs) {
            $fixedClass = (in_array($sectionId, $fixedWidthSections) ? 'fixedWidthFont' : '');

            foreach ($paragraphs as $paragraphNo => $parDat) {
                $nameBase = 'newParas[' . $sectionId . '][' . $paragraphNo . ']';
                ?>
                <section class="paragraph paragraph_<?= $sectionId ?>_<?= $paragraphNo ?> unmodified"
                         data-unchanged-amendment="<?= Html::encode($parDat['plain']) ?>"
                         data-section-id="<?= $sectionId ?>" data-paragraph-no="<?= $paragraphNo ?>">
                    <h2 class="green">
                        <?php
                        if ($parDat['lineFrom'] == $parDat['lineTo']) {
                            $tpl = \Yii::t('amend', 'merge1_changein_1');
                        } else {
                            $tpl = \Yii::t('amend', 'merge1_changein_x');
                        }
                        echo str_replace(['%LINEFROM%', '%LINETO%'], [$parDat['lineFrom'], $parDat['lineTo']], $tpl);
                        ?>:
                    </h2>
                    <div class="content">
                        <div class="modifySelector">
                            <label>
                                <input type="radio" name="modified_<?=$sectionId?>_<?=$paragraphNo?>" value="0" checked>
                                <?= \Yii::t('amend', 'merge1_use_unchanged') ?>
                            </label>
                            <label>
                                <input type="radio" name="modified_<?=$sectionId?>_<?=$paragraphNo?>" value="1">
                                <?= \Yii::t('amend', 'merge1_use_modified') ?>
                            </label>
                        </div>
                        <div class="unmodifiedVersion motionTextHolder">
                            <div class="paragraph">
                                <div class="text <?= $fixedClass ?>"><?= $parDat['diff'] ?></div>
                            </div>
                        </div>
                        <div class="affectedBlock">
                            <textarea name="<?= $nameBase ?>" class="modifiedText" title=""></textarea>
                            <div id="new_paragraphs_<?= $sectionId ?>_<?= $paragraphNo ?>"
                                 class="<?= $fixedClass ?> texteditor texteditorBox"
                                 title="<?= \Yii::t('amend', 'merge1_modify_title') ?>" data-track-changed="1">
                                <?= $parDat['plain'] ?>
                            </div>
                        </div>
                    </div>
                </section>
                <?php
            }
        }

        echo '<div class="checkButtonRow">';
        if ($needsCollissionCheck) {
            $url = UrlHelper::createAmendmentUrl($amendment, 'get-merge-collissions');
            echo '<button class="checkAmendmentCollissions btn btn-primary" data-url="' . Html::encode($url) . '">' .
                \Yii::t('amend', 'merge1_check_collissions') . '</button>';
        } else {
            echo '<button type="submit" name="save" class="btn btn-primary save">' .
                \Yii::t('admin', 'save') . '</button>';
        }
        echo '</div>';
        ?>
    </fieldset>
</div>