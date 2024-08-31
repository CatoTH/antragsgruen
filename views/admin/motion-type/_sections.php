<?php

use app\models\db\ConsultationSettingsMotionSection;
use app\models\sectionTypes\{ISectionType, TabularDataType};
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var ConsultationSettingsMotionSection $section
 */

$settings = $section->getSettingsObj();
$sectionId = intval($section->id);
if ($sectionId === 0) {
    $sectionId = '#NEW#';
}
$sName = 'sections[' . $sectionId . ']';

?>
<li data-id="<?= $sectionId ?>" class="section<?= $sectionId ?>">
    <span class="drag-handle">&#9776;</span>
    <div class="sectionContent">
        <div class="toprow">

            <button type="button" class="btn-link remover" title="<?= Html::encode(Yii::t('admin', 'motion_section_del')) ?>">
                <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
                <span class="sr-only"><?= Yii::t('admin', 'motion_section_del') ?></span>
            </button>

            <label for="sectionType<?= $sectionId ?>" class="sr-only"><?= Yii::t('admin', 'motion_section_type') ?></label>
            <?php
            $attribs = ['class' => 'form-control sectionType', 'id' => 'sectionType' . $sectionId];
            if ($section->id > 0) {
                $attribs['disabled'] = 'disabled';
            }
            echo Html::dropDownList(
                $sName . '[type]',
                $section->type,
                ISectionType::getTypes(),
                $attribs
            );
            ?>
            <label class="sectionTitle"><span class="sr-only"><?= Yii::t('admin', 'motion_section_name') ?></span>
                <input type="text" name="<?= $sName ?>[title]" value="<?= Html::encode($section->title ?: '') ?>"
                       required placeholder="<?= Yii::t('admin', 'motion_section_name_p') ?>" class="form-control">
            </label>

        </div>
        <div class="bottomrow">
            <div class="leftCol">
                <fieldset class="positionRow">
                    <legend><?= Yii::t('admin', 'motion_type_pos') ?></legend>
                    <label class="positionSection">
                        <?= Html::radio($sName . '[positionRight]', ($section->positionRight != 1), ['value' => 0]) ?>
                        <?= Yii::t('admin', 'motion_type_pos_left') ?>
                    </label><br>
                    <label class="positionSection">
                        <?= Html::radio($sName . '[positionRight]', ($section->positionRight == 1), ['value' => 1]) ?>
                        <?= Yii::t('admin', 'motion_type_pos_right') ?>
                    </label><br>
                </fieldset>

                <label class="printTitleSection">
                    <?= Html::checkbox($sName . '[printTitle]', $section->printTitle) ?>
                    <?= Yii::t('admin', 'motion_type_print_title') ?>
                </label>

                <label class="showInHtml">
                    <?= Html::checkbox($sName . '[showInHtml]', $settings->showInHtml) ?>
                    <?= Yii::t('admin', 'motion_type_show_in_html') ?>
                </label>

                <label class="hasExplanation">
                    <?= Html::checkbox($sName . '[hasExplanation]', $section->getSettingsObj()->explanationHtml) ?>
                    <?= Yii::t('admin', 'motion_type_has_explanation') ?>
                </label>
            </div>
            <div class="optionsCol">
                <label class="fixedWidthLabel">
                    <?= Html::checkbox($sName . '[fixedWidth]', $section->fixedWidth, ['class' => 'fixedWidth']) ?>
                    <?= Yii::t('admin', 'motion_section_fixed_width') ?>
                </label>

                <label class="isRtlLabel">
                    <?= Html::checkbox($sName . '[isRtl]', $section->getSettingsObj()->isRtl, ['class' => 'isRtl']) ?>
                    <?= Yii::t('admin', 'motion_section_rtl') ?>
                </label>

                <label class="lineNumbersLabel">
                    <?= Html::checkbox($sName . '[lineNumbers]', $section->lineNumbers, ['class' => 'lineNumbers']) ?>
                    <?= Yii::t('admin', 'motion_section_line_numbers') ?>
                </label>

                <label class="maxLength">
                    <?= Html::checkbox($sName . '[maxLenSet]', ($section->maxLen !== 0 && $section->maxLen !== null), ['class' => 'maxLenSet']) ?>
                    <?= Yii::t('admin', 'motion_section_limit') ?>
                </label>

                <fieldset class="imageMaxSize">
                    <legend><?= Yii::t('admin', 'motion_section_maxsize') ?></legend>
                    <div>
                        <input type="number" name="<?= $sName ?>[imgMaxWidth]" value="<?= $settings->imgMaxWidth > 0 ? $settings->imgMaxWidth : '' ?>"
                               title="Width in cm" size="4" class="form-control">
                        x
                        <input type="number" name="<?= $sName ?>[imgMaxHeight]" value="<?= $settings->imgMaxHeight > 0 ? $settings->imgMaxHeight : '' ?>"
                               title="Height in cm" size="4" class="form-control">
                        cm
                    </div>
                </fieldset>

                <?php
                $value = '';
                if ($section->maxLen > 0) {
                    $value = intval($section->maxLen);
                }
                if ($section->maxLen < 0) {
                    $value = -1 * intval($section->maxLen);
                }
                ?>
                <label class="maxLenInput">
                    <input type="number" min="1" name="<?= $sName ?>[maxLenVal]" value="<?= $value ?>">
                    <?= Yii::t('admin', 'motion_section_chars') ?>
                </label>

                <label class="maxLenSoft">
                    <?= Html::checkbox($sName . '[maxLenSoft]', ($section->maxLen < 0), ['class' => 'maxLenSoftCheckbox']) ?>
                    <?= Yii::t('admin', 'motion_section_limit_soft') ?>
                </label>

                <label class="nonPublicRow">
                    <?php
                    $checked = ($section->getSettingsObj()->public === \app\models\settings\MotionSection::PUBLIC_NO);
                    $options = ['class' => 'nonPublic'];
                    if ($sectionId !== '#NEW#') {
                        $options['disabled'] = 'disabled';
                    }
                    echo Html::checkbox($sName . '[nonPublic]', $checked, $options);
                    ?>
                    <?= Yii::t('admin', 'motion_section_nonpublic') ?>
                    <?= \app\components\HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_section_nonpublic_h')) ?>
                </label>

                <label class="amendmentRow">
                    <?= Html::checkbox(
                        $sName . '[hasAmendments]',
                        ($section->hasAmendments === 1),
                        ['class' => 'hasAmendments']
                    ) ?>
                    <?= Yii::t('admin', 'motion_section_amendable') ?>
                </label>
            </div>
            <div class="commAmendCol">
                <fieldset class="commentRow">
                    <legend><?= Yii::t('admin', 'motion_section_comm') ?>:</legend>

                    <label class="commentNone">
                        <?php
                        $val = ConsultationSettingsMotionSection::COMMENTS_NONE;
                        echo Html::radio($sName . '[hasComments]', ($section->hasComments === $val), ['value' => $val])
                        ?>
                        <?= Yii::t('admin', 'motion_section_comm_none') ?>
                    </label>

                    <label class="commentSection">
                        <?php
                        $val = ConsultationSettingsMotionSection::COMMENTS_MOTION;
                        echo Html::radio($sName . '[hasComments]', ($section->hasComments === $val), ['value' => $val]);
                        ?>
                        <?= Yii::t('admin', 'motion_section_comm_whole') ?>
                    </label>

                    <label class="commentParagraph">
                        <?php
                        $val = ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS;
                        echo Html::radio($sName . '[hasComments]', ($section->hasComments === $val), ['value' => $val]);
                        ?>
                        <?= Yii::t('admin', 'motion_section_comm_para') ?>
                    </label>
                </fieldset>

                <fieldset class="requiredRow">
                    <legend><?= Yii::t('admin', 'motion_section_required') ?>:</legend>

                    <label class="requiredNo">
                        <?php
                        $val = ConsultationSettingsMotionSection::REQUIRED_NO;
                        echo Html::radio($sName . '[required]', ($section->required === $val), ['value' => $val])
                        ?>
                        <?= Yii::t('admin', 'motion_section_required_no') ?>
                    </label>

                    <label class="requiredEncouraged">
                        <?php
                        $val = ConsultationSettingsMotionSection::REQUIRED_ENCOURAGED;
                        echo Html::radio($sName . '[required]', ($section->required === $val), ['value' => $val])
                        ?>
                        <?= Yii::t('admin', 'motion_section_required_enc') ?>
                        <?= \app\components\HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_section_required_enc_h')) ?>
                    </label>

                    <label class="requiredYes">
                        <?php
                        $val = ConsultationSettingsMotionSection::REQUIRED_YES;
                        echo Html::radio($sName . '[required]', ($section->required === $val), ['value' => $val])
                        ?>
                        <?= Yii::t('admin', 'motion_section_required_yes') ?>
                    </label>
            </div>
        </div>

        <div class="explanationRow">
            <textarea placeholder="<?= Yii::t('admin', 'motion_type_has_explanation') ?>"
                      name="<?= $sName ?>[explanationHtml]" class="form-control" rows="2"><?php
                echo Html::encode($section->getSettingsObj()->explanationHtml ?? '');
            ?></textarea>
        </div>

        <?php
        $dataRowFormatter = function (TabularDataType $row, int $i, string $sectionName): string {
            $str = '<li class="no' . $i . '">';
            $str .= '<span class="drag-data-handle">&#9776;</span>';
            $str .= '<input type="text" name="' . $sectionName . '[tabular][' . $row->rowId . '][title]"';
            $str .= ' placeholder="' . Yii::t('admin', 'motion_section_tab_p') . '" value="' . Html::encode($row->title) . '" class="form-control">';
            $str .= '<select name="' . $sectionName . '[tabular][' . $row->rowId . '][type]" class="form-control tabularTypeSelect">';
            foreach (TabularDataType::getDataTypes() as $dataId => $dataName) {
                $str .= '<option value="' . $dataId . '"';
                if ($row->type == $dataId) {
                    $str .= ' selected';
                }
                $str .= '>' . Html::encode($dataName) . '</option>';
            }
            $str .= '</select>';
            $str .= '<button type="button" class="delRow btn btn-link" title="' . Yii::t('admin', 'motion_section_rowdel') . '">';
            $str .= '<span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>';
            $str .= '<span class="sr-only">' . Yii::t('admin', 'motion_section_rowdel') . '</span>';
            $str .= '</button>';
            $str .= '<div class="selectOptions hidden">';
            $str .= '<div class="description">' . Yii::t('admin', 'motion_section_options') . ':</div>';
            $str .= '<div class="selectize-wrapper">';
            $str .= '<select class="tags" name="' . $sectionName . '[tabular][' . $row->rowId . '][options][]" multiple="multiple">';
            foreach ($row->options as $option) {
                $str .= '<option value="' . Html::encode($option) . '" selected>' . Html::encode($option) . '</option>';
            }
            $str .= '</select></div>';
            $str .= '</div>';
            $str .= '</li>';
            return $str;
        };

        ?>
        <fieldset class="tabularDataRow">
            <legend><?= Yii::t('admin', 'motion_section_tab_data') ?>:</legend>
            <ul>
                <?php
                if ($section->type === ISectionType::TYPE_TABULAR) {
                    $rows = \app\models\sectionTypes\TabularData::getTabularDataRowsFromData($section->data);
                    $i    = 0;

                    foreach ($rows as $rowId => $row) {
                        echo $dataRowFormatter($row, $i++, $sName);
                    }
                }
                ?>
            </ul>
            <?php
            $newRow   = new TabularDataType([
                'rowId' => '#NEWDATA#',
                'type'  => TabularDataType::TYPE_STRING,
                'title' => ''
            ]);
            $template = $dataRowFormatter($newRow, 0, $sName);
            ?>
            <button type="button" class="btn btn-link addRow" data-template="<?= Html::encode($template) ?>">
                <span class="glyphicon glyphicon-plus-sign" aria-hidden="true"></span>
                <?= Yii::t('admin', 'motion_section_add_line') ?>
            </button>
        </fieldset>

    </div>
</li>
