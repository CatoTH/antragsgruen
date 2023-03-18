<?php

use app\models\db\{Consultation, ConsultationSettingsTag};
use yii\helpers\Html;

/**
 * @var int[] $tagIds
 * @var Consultation $consultation
 */

if (!$consultation->getSettings()->allowUsersToSetTags) {
    return;
}

/** @var ConsultationSettingsTag[] $tags */
$tags = [];
foreach ($consultation->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
    $tags[$tag->id] = $tag;
}

if (count($tags) === 0) {
    return;
}

if ($consultation->getSettings()->allowMultipleTags) {
    echo '<fieldset class="form-group multipleTagsGroup">';
    echo '<legend class="legend">' . Yii::t('motion', 'tag_tags') . '</legend>';
    foreach ($tags as $id => $tag) {
        echo '<label class="checkbox-inline"><input name="tags[]" value="' . $id . '" type="checkbox" ';
        if (in_array($id, $tagIds)) {
            echo ' checked';
        }
        echo ' title="' . Yii::t('motion', 'tag_tags') . '"> ' . Html::encode($tag->title) . '</label>';
    }
    echo '</fieldset>';
} elseif (count($tags) === 1) {
    $keys = array_keys($tags);
    echo '<input type="hidden" name="tags[]" value="' . $keys[0] . '">';
} else {
    $selected = (count($tagIds) > 0 ? $tagIds[0] : 0);
    $tagOptions = [];
    foreach ($tags as $tag) {
        $tagOptions[$tag->id] = $tag->title;
    }
    echo '<div class="form-group">';
    echo '<label class="legend" for="tagSelect">' . Yii::t('motion', 'tag_tags') . '</label><div style="position: relative;">';
    echo Html::dropDownList('tags[]', $selected, $tagOptions, ['id' => 'tagSelect', 'class' => 'stdDropdown']);
    echo '</div>';
    echo '</div>';
}

