<?php

use app\components\UrlHelper;
use app\models\layoutHooks\Layout;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

ob_start();
?>
<section aria-labelledby="sortVotingsHeader">
    <h2 class="green" id="sortVotingsHeader"><?= Yii::t('voting', 'settings_sort_title') ?></h2>
    <div class="content">
        <draggable :list="votings" item-key="title">
            <template #item="{ element }">
                <div class="list-group-item">
                    {{ element.title }}
                </div>
            </template>
        </draggable>
    </div>
</section>

<?php
$html = ob_get_clean();
?>

<script>
    __setVueComponent('voting', 'component', 'draggable', vuedraggable);

    __setVueComponent('voting', 'component', 'voting-sort-widget', {
        template: <?= json_encode($html) ?>,
        props: ['votings'],
        data() {
            return {
                list: [
                    {name: "John", id: 0},
                    {name: "Joao", id: 1},
                    {name: "Jean", id: 2}
                ],
            }
        }
    });
</script>
