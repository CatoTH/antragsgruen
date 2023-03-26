<?php

declare(strict_types=1);

use app\components\UrlHelper;
use app\models\db\{ConsultationSettingsTag, Motion};
use yii\helpers\Html;

/**
 * @var Motion $motion
 */

$submitUrl = UrlHelper::createUrl(['/dbwv/admin-workflow/assign-main-tag', 'motionSlug' => $motion->getMotionSlug()]);

$tagSelect = [
    '' => '- nicht zugeordnet -',
];
foreach ($motion->getMyConsultation()->getSortedTags(ConsultationSettingsTag::TYPE_PUBLIC_TOPIC) as $tag) {
    $tagSelect[$tag->id] = $tag->title;
}
$selectedTagId = (count($motion->getPublicTopicTags()) > 0 ? (string)$motion->getPublicTopicTags()[0]->id : '');

echo Html::beginForm($submitUrl, 'POST', [
    'id' => 'dbwv_assign_main_tag',
]);


$options = ['id' => 'dbwv_main_tagSelect', 'class' => 'stdDropdown', 'required' => 'required'];
echo Html::dropDownList('tag', $selectedTagId, $tagSelect, $options);

?>

<script>
    (() => {
        const tagSelect = document.getElementById('dbwv_main_tagSelect');
        tagSelect.addEventListener('change', () => {
            document.getElementById('dbwv_assign_main_tag').submit();
        });
    })();
</script>

<?php

echo Html::endForm();
