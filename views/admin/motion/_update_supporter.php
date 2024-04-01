<?php

use app\models\db\ISupporter;
use app\models\settings\InitiatorForm;
use app\models\supportTypes\SupportBase;
use yii\helpers\Html;

/**
 * @var ISupporter[] $supporters
 * @var ISupporter $newTemplate
 * @var InitiatorForm $settings
 */


$getSupporterRow = function (ISupporter $supporter) use ($settings): string {
    $str = '<li><div class="supporterRow">';
    $str .= '<input type="hidden" name="supporterId[]" value="' . Html::encode($supporter->id ?: '') . '">';

    $title = Html::encode(Yii::t('admin', 'motion_supp_name'));
    $str   .= '<div class="nameCol">';

    $str .= '<span class="glyphicon glyphicon-resize-vertical moveHandle"></span> ';

    $str .= '<input type="text" name="supporterName[]" value="' . Html::encode($supporter->name ?: '') . '" ';
    $str .= ' class="form-control supporterName" placeholder="' . $title . '" title="' . $title . '">';
    $str .= '</div>';

    $title = Html::encode(Yii::t('admin', 'motion_supp_orga'));
    $str   .= '<div>';
    $str   .= '<input type="text" name="supporterOrga[]" value="' . Html::encode($supporter->organization ?: '') . '" ';
    $str   .= ' class="form-control supporterOrga" placeholder="' . $title . '" title="' . $title . '">';
    $str   .= '</div>';

    if ($settings->contactGender !== InitiatorForm::CONTACT_NONE) {
        $genderChoices = array_merge(
            ['' => Yii::t('initiator', 'gender') . ':'],
            SupportBase::getGenderSelection()
        );
        $str .= '<div class="colGender">';
        $str .= Html::dropDownList(
            'supporterGender[]',
            $supporter->getExtraDataEntry(ISupporter::EXTRA_DATA_FIELD_GENDER),
            $genderChoices,
            ['class' => 'stdDropdown']
        );
        $str .= '</div>';
    }

    $str .= '<div>';
    $str .= '<button type="button" class="btn btn-link delSupporter" aria-label="' . Yii::t('base', 'aria_remove') . '">' .
            '<span class="glyphicon glyphicon-minus-sign" aria-hidden="true"></span></button>';
    if ($supporter->user) {
        $str .= Html::encode($supporter->user->getAuthName());
    }
    $str .= '</div>';


    $str .= '</div></li>';
    return $str;
};

?>
<h2 class="green"><?= Yii::t('admin', 'motion_edit_supporters') ?></h2>
<div class="content" id="motionSupporterHolder">
    <ul class="supporterList">
        <?php
        foreach ($supporters as $supporter) {
            echo $getSupporterRow($supporter);
        }
        ?>
    </ul>

    <div class="fullTextAdder">
        <button type="button" class="btn btn-link">
            <?= Yii::t('initiator', 'fullTextField') ?>
        </button>
    </div>

    <button type="button" class="btn btn-link supporterRowAdder" data-content="<?= Html::encode($getSupporterRow($newTemplate)) ?>">
        <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
        <?= Yii::t('admin', 'motion_edit_supporters_add') ?>
    </button>

    <div class="hidden" id="supporterFullTextHolder">
        <div class="textHolder">
            <label for="fullTextHolderTextarea" class="sr-only"><?= Html::encode(Yii::t('initiator', 'fullTextSyntax')) ?></label>
            <textarea class="form-control" placeholder="<?= Html::encode(Yii::t('initiator', 'fullTextSyntax')) ?>"
                      id="fullTextHolderTextarea" rows="10"></textarea>
        </div>
        <div class="btnHolder">
            <button type="button" class="btn btn-success fullTextAdd">
                <span class="glyphicon glyphicon-plus"></span>
                <?= Yii::t('initiator', 'fullTextAdd') ?>
            </button>

            <button type="button" class="btn btn-default fullTextCopy">
                <span class="glyphicon glyphicon-copy normal"></span>
                <span class="glyphicon glyphicon-ok ok"></span>
                <?= Yii::t('initiator', 'copy_to_clipboard') ?>
            </button>
            <?= \app\components\HTMLTools::getTooltipIcon(Yii::t('initiator', 'copy_to_clipboard_h')) ?>
        </div>
    </div>
</div>
