<?php

use app\components\Diff\SingleAmendmentMergeViewParagraphData;
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

/**
 * @var Amendment $amendment
 * @var SingleAmendmentMergeViewParagraphData[][] $paragraphSections
 * @var bool $needsCollisionCheck
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
            $parForm = (in_array($sectionId, $fixedWidthSections) ? 'fixedWidthFont' : '');

            foreach ($paragraphs as $paragraphNo => $parDat) {
                $nameBase   = 'newParas[' . $sectionId . '][' . $paragraphNo . ']';
                $formSecPar = $sectionId . '_' . $paragraphNo;
                ?>
                <section class="paragraph paragraph_<?= $sectionId ?>_<?= $paragraphNo ?> unmodified"
                         data-unchanged-amendment="<?= Html::encode($parDat->plain) ?>"
                         data-modified-amendment="<?= Html::encode($parDat->modPlain ? $parDat->modPlain : '') ?>"
                         data-section-id="<?= $sectionId ?>" data-paragraph-no="<?= $paragraphNo ?>">
                    <h2 class="green">
                        <?php
                        if ($parDat->lineFrom === $parDat->lineTo) {
                            $tpl = Yii::t('amend', 'merge1_changein_1');
                        } else {
                            $tpl = Yii::t('amend', 'merge1_changein_x');
                        }
                        echo str_replace(['%LINEFROM%', '%LINETO%'], [$parDat->lineFrom, $parDat->lineTo], $tpl);
                        ?>:
                    </h2>
                    <div class="content">
                        <div class="selectorToolbar">
                            <?php
                            if ($parDat->modDiff) {
                                ?>
                                <div class="versionSelector">
                                    <label>
                                        <input type="radio" name="version_<?= $formSecPar ?>" value="original">
                                        <?= Yii::t('amend', 'merge1_use_original') ?>
                                    </label>
                                    <label>
                                        <input type="radio" name="version_<?= $formSecPar ?>" value="modified" checked>
                                        <?= Yii::t('amend', 'merge1_use_modified') ?>
                                    </label>
                                </div>
                                <?php
                            }
                            ?>
                            <div class="modifySelector">
                                <label>
                                    <input type="checkbox" name="modify_<?= $formSecPar ?>">
                                    <?= Yii::t('amend', 'merge1_change_text') ?>
                                </label>
                            </div>
                        </div>
                        <div class="originalVersion motionTextHolder">
                            <div class="paragraph">
                                <div class="text motionTextFormattings <?= $parForm ?>"><?= $parDat->diff ?></div>
                            </div>
                        </div>
                        <?php
                        if ($parDat->modDiff) {
                            ?>
                            <div class="modifiedVersion motionTextHolder">
                                <div class="paragraph">
                                    <div
                                        class="text motionTextFormattings <?= $parForm ?>"><?= $parDat->modDiff ?></div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                        <textarea name="<?= $nameBase ?>" class="modifiedText" title=""></textarea>
                        <div class="originalVersion modifyText">
                            <div id="new_paragraphs_original_<?= $formSecPar ?>"
                                 class="<?= $parForm ?> texteditor motionTextFormattings texteditorBox"
                                 title="<?= Yii::t('amend', 'merge1_modify_title') ?>" data-track-changed="1">
                                <?= $parDat->plain ?>
                            </div>
                        </div>
                        <?php
                        if ($parDat->modDiff) {
                            ?>
                            <div class="modifiedVersion modifyText">
                                <div id="new_paragraphs_modified_<?= $formSecPar ?>"
                                     class="<?= $parForm ?> texteditor motionTextFormattings texteditorBox"
                                     title="<?= Yii::t('amend', 'merge1_modify_title') ?>" data-track-changed="1">
                                    <?= $parDat->modPlain ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </section>
                <?php
            }
        }

        echo '<div class="checkButtonRow">';
        if ($needsCollisionCheck) {
            $url = UrlHelper::createAmendmentUrl($amendment, 'get-merge-collisions');
            echo '<button class="checkAmendmentCollisions btn btn-primary" data-url="' . Html::encode($url) . '">' .
                Yii::t('amend', 'merge1_check_collisions') . '</button>';
        } else {
            echo '<button type="submit" name="save" class="btn btn-primary save">' .
                Yii::t('admin', 'save') . '</button>';
        }
        echo '</div>';
        ?>
    </fieldset>
</div>
