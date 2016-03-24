<?php
use app\models\db\ConsultationSettingsMotionSection;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TabularDataType;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var ConsultationSettingsMotionSection $section
 */

$sectionId = IntVal($section->id);
if ($sectionId == 0) {
    $sectionId = '#NEW#';
}
$sectionName = 'sections[' . $sectionId . ']';

?>
<li data-id="<?= $sectionId ?>" class="section<?= $sectionId ?>">
    <span class="drag-handle">&#9776;</span>
    <div class="sectionContent">
        <div class="toprow">

            <a href="#" class="remover" title="<?= Html::encode(\Yii::t('admin', 'motion_section_del')) ?>">
                <span class="glyphicon glyphicon-remove-circle"></span>
            </a>
            <?php

            $attribs = ['class' => 'form-control sectionType'];
            if ($section->id > 0) {
                $attribs['disabled'] = 'disabled';
            }
            echo Html::dropDownList(
                $sectionName . '[type]',
                $section->type,
                ISectionType::getTypes(),
                $attribs
            );
            ?>
            <label class="sectionTitle"><span class="sr-only"><?=Yii::t('admin', 'motion_section_name')?></span>
                <input type="text" name="<?= $sectionName ?>[title]" value="<?= Html::encode($section->title) ?>" required placeholder="Titel" class="form-control">
            </label>

        </div>
        <div class="bottomrow">
            <div class="positionRow">

                <div><?= \Yii::t('admin', 'motion_type_pos') ?></div>
                <label class="positionSection">
                    <?= Html::radio($sectionName . '[positionRight]', ($section->positionRight != 1), ['value' => 0]) ?>
                    <?= \Yii::t('admin', 'motion_type_pos_left') ?>
                </label><br>
                <label class="positionSection">
                    <?= Html::radio($sectionName . '[positionRight]', ($section->positionRight == 1), ['value' => 1]) ?>
                    <?= \Yii::t('admin', 'motion_type_pos_right') ?></label><br>

            </div>
            <div class="optionsRow">

                <label class="fixedWidthLabel">
                    <?= Html::checkbox($sectionName . '[fixedWidth]', $section->fixedWidth, ['class' => 'fixedWidth']) ?>
                    <?= Yii::t('admin', 'motion_section_fixed_width') ?>
                </label>

                <label class="requiredLabel">
                    <?= Html::checkbox($sectionName . '[required]', $section->required, ['class' => 'required']) ?>
                    <?= Yii::t('admin', 'motion_section_required') ?>
                </label>

                <label class="lineNumbersLabel">
                    <?= Html::checkbox($sectionName . '[lineNumbers]', $section->lineNumbers, ['class' => 'lineNumbers']) ?>
                    <?= Yii::t('admin', 'motion_section_line_numbers') ?>
                </label>

                <label class="lineLength">
                    <?= Html::checkbox($sectionName . '[maxLenSet]', ($section->maxLen != 0), ['class' => 'maxLenSet']) ?>
                    <?= Yii::t('admin', 'motion_section_limit') ?></label>

                <label class="maxLenInput"><input type="number" min="1" name="<?=$sectionName?>[maxLenVal]" value="<?php
                    if ($section->maxLen > 0) {
                        echo $section->maxLen;
                    }
                    if ($section->maxLen < 0) {
                        echo -1 * $section->maxLen;
                    }
                    ?>"> <?= Yii::t('admin', 'motion_section_chars') ?></label>

                <label class="lineLengthSoft">
                    <?= Html::checkbox($sectionName . '[maxLenSoft]', ($section->maxLen < 0), ['class' => 'maxLenSoft']) ?>
                    <?= Yii::t('admin', 'motion_section_limit_soft') ?>
                </label>

            </div>
            <div class="commAmendRow">

                <div class="commentRow">
                    <div><?= Yii::t('admin', 'motion_section_comm') ?>:</div>

                    <label class="commentNone">
                        <?php
                        $val = ConsultationSettingsMotionSection::COMMENTS_NONE;
                        echo Html::radio($sectionName . '[hasComments]', ($section->hasComments == $val), ['value' => $val])
                        ?>
                        <?= Yii::t('admin', 'motion_section_comm_none') ?>
                    </label>

                    <label class="commentSection">
                        <?php
                        $val = ConsultationSettingsMotionSection::COMMENTS_MOTION;
                        echo Html::radio($sectionName . '[hasComments]', ($section->hasComments == $val), ['value' => $val]);
                        ?>
                        <?= Yii::t('admin', 'motion_section_comm_whole') ?>
                    </label>

                    <label class="commentParagraph">
                        <?php
                        $val = ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS;
                        echo Html::radio($sectionName . '[hasComments]', ($section->hasComments == $val), ['value' => $val]);
                        ?>
                        <?= Yii::t('admin', 'motion_section_comm_para') ?>
                    </label>

                </div>

                <label class="amendmentRow">
                    <?= Html::checkbox(
                        $sectionName . '[hasAmendments]',
                        ($section->hasAmendments == 1),
                        ['class' => 'hasAmendments']
                    ) ?>
                    <?= Yii::t('admin', 'motion_section_amendable') ?>
                </label>
            </div>
        </div>
        
        <?php
        /**
         * @param TabularDataType $row
         * @param int $i
         * @param string $sectionName
         * @return string
         */
        $dataRowFormatter = function (TabularDataType $row, $i, $sectionName) {
            $str = '<li class="no' . $i . '">';
            $str .= '<span class="drag-data-handle">&#9776;</span>';
            $str .= '<input type="text" name="' . $sectionName . '[tabular][' . $row->rowId . '][title]"';
            $str .= ' placeholder="Angabe" value="' . Html::encode($row->title) . '" class="form-control">';
            $str .= '<select name="' . $sectionName . '[tabular][' . $row->rowId . '][type]" class="form-control">';
            foreach (TabularDataType::getDataTypes() as $dataId => $dataName) {
                $str .= '<option value="' . $dataId . '"';
                if ($row->type == $dataId) {
                    $str .= ' selected';
                }
                $str .= '>' . Html::encode($dataName) . '</option>';
            }
            $str .= '</select>';
            $str .= '<a href="#" class="delRow glyphicon glyphicon-remove-circle"></a>';
            $str .= '</li>';
            return $str;
        };

        ?>
        <div class="tabularDataRow">
            <legend><?= Yii::t('admin', 'motion_section_tab_data') ?>:</legend>
            <ul>
                <?php
                if ($section->type == ISectionType::TYPE_TABULAR) {
                    $rows = \app\models\sectionTypes\TabularData::getTabularDataRowsFromData($section->data);
                    $i    = 0;

                    foreach ($rows as $rowId => $row) {
                        echo $dataRowFormatter($row, $i++, $sectionName);
                    }
                }
                ?>
            </ul>
            <?php
            $newRow   = new TabularDataType(['rowId' => '#NEWDATA#', 'type' => TabularDataType::TYPE_STRING, 'title' => '']);
            $template = $dataRowFormatter($newRow, 0, $sectionName);
            ?>
            <a href="#" class="addRow" data-template="<?= Html::encode($template) ?>">
                <span class="glyphicon glyphicon-plus-sign"></span> <?= Yii::t('admin', 'motion_section_add_line') ?></a>
        </div>

    </div>
</li>