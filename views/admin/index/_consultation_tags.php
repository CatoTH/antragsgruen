<?php

use app\models\db\Consultation;
use app\models\db\ConsultationSettingsTag;
use yii\helpers\Html;

/**
 * @var Consultation $consultation
 * @var \app\models\settings\Consultation $settings
 */

$tagTypes = [
    ConsultationSettingsTag::TYPE_PUBLIC_TOPIC,
    ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE,
];

?>

<h2 class="green" id="conTopicsTitle"><?= Yii::t('admin', 'con_topics') ?></h2>
<section id="tagsEditForm" aria-labelledby="conTopicsTitle" class="content" data-delete-warnings="<?= Html::encode(Yii::t('admin', 'con_topic_del_warn')) ?>">

    <div class="tagTypeSelector">
        <div class="btn-group" data-toggle="buttons">
            <label class="btn btn-sm btn-default active">
                <input type="radio" name="tagType" value="<?= ConsultationSettingsTag::TYPE_PUBLIC_TOPIC ?>" autocomplete="off" checked>
                <?= Yii::t('admin', 'con_topics_public') ?>
            </label>
            <label class="btn btn-sm btn-default">
                <input type="radio" name="tagType" value="<?= ConsultationSettingsTag::TYPE_PROPOSED_PROCEDURE ?>" autocomplete="off">
                <?= Yii::t('admin', 'con_topics_procedure') ?>
            </label>
        </div>
    </div>

    <?php
    foreach ($tagTypes as $tagType) {
        $tags = $consultation->getSortedTags($tagType);
        ?>
        <ol class="stdNonFormattedList editList editList<?= $tagType ?>">
            <?php
            foreach ($tags as $tag) {
                $hasIMotions = (count($tag->motions) > 0 || count($tag->amendments) > 0);
                ?>
                <li data-has-imotions="<?= $hasIMotions ? 1 : 0 ?>">
                    <input type="hidden" name="tags[id][]" value="<?= $tag->id ?>">
                    <input type="hidden" name="tags[type][]" value="<?= $tag->type ?>" class="tagTypeInput">
                    <span class="drag-handle">&#9776;</span>

                    <label class="tagTitle">
                        <input type="text" name="tags[title][]" value="<?= Html::encode($tag->title) ?>"
                               required class="form-control" title="<?= Html::encode(Yii::t('admin', 'con_topic_title')) ?>">
                    </label>

                    <button type="button" class="btn-link remover" title="<?= Html::encode(Yii::t('admin', 'con_topic_del')) ?>">
                        <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
                        <span class="sr-only"><?= Yii::t('admin', 'con_topic_del') ?></span>
                    </button>
                </li>
                <?php
            }
            if ($tagType === ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) { // This is only a template and will be removed from the DOM by JS
                ?>
                <li class="newTagRowTemplate" data-has-imotions="0">
                    <input type="hidden" name="tags[id][]" value="NEW">
                    <input type="hidden" name="tags[type][]" value="OVERWRITE_ME" class="tagTypeInput">
                    <span class="drag-handle">&#9776;</span>

                    <label class="tagTitle">
                        <input type="text" name="tags[title][]" value="" autocomplete="off"
                               class="form-control" title="<?= Html::encode(Yii::t('admin', 'con_topic_title')) ?>">
                    </label>

                    <button type="button" class="btn-link remover" title="<?= Html::encode(Yii::t('admin', 'con_topic_del')) ?>">
                        <span class="glyphicon glyphicon-remove-circle" aria-hidden="true"></span>
                        <span class="sr-only"><?= Yii::t('admin', 'con_topic_del') ?></span>
                    </button>
                </li>
            <?php } ?>
        </ol>
    <?php } ?>
    <div class="adderRow">
        <button class="btn btn-link tagAdderBtn" type="button">
            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
            <?= Yii::t('admin', 'con_topic_add') ?>
        </button>
    </div>

    <div><label>
            <?php
            $handledSettings[] = 'allowUsersToSetTags';
            echo Html::checkbox(
                'settings[allowUsersToSetTags]',
                $settings->allowUsersToSetTags,
                ['id' => 'allowUsersToSetTags']
            );
            echo ' ' . Yii::t('admin', 'con_allow_set_tags');
            ?>
        </label></div>
    <div><label>
            <?php
            $handledSettings[] = 'allowMultipleTags';
            echo Html::checkbox(
                'settings[allowMultipleTags]',
                $settings->allowMultipleTags,
                ['id' => 'allowMultipleTags']
            );
            echo ' ' . Yii::t('admin', 'con_multiple_topics');
            ?>
        </label></div>
    <div><label>
            <?php
            $handledSettings[] = 'amendmentsHaveTags';
            echo Html::checkbox(
                'settings[amendmentsHaveTags]',
                $settings->amendmentsHaveTags,
                ['id' => 'amendmentsHaveTags']
            );
            echo ' ' . Yii::t('admin', 'con_amendment_tags');
            ?>
        </label></div>
</section>
