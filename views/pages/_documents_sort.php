<?php

use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\ConsultationFileGroup[] $fileGroups
 */

/** @var \app\controllers\Base $controller */

$controller = $this->context;
$consultation = $controller->consultation;

$documentsJs = array_map(function (\app\models\db\ConsultationFileGroup $fileGroup): array {
    return [
        'id' => $fileGroup->id,
        'title' => $fileGroup->title,
    ];
}, $fileGroups);


?>
<section aria-labelledby="sortDocumentsHeader" class="documentSortingForm stdSortingWidget hidden">
    <h2 class="green" id="sortDocumentsHeader"><?= Yii::t('pages', 'documents_sort') ?></h2>
    <div class="content">
        <?php
        echo Html::beginForm();
        ?>
        <div id="sortDocumentsHolder"></div>
        <?php
        echo Html::endForm();
        ?>
    </div>
</section>

<script>
    window.addEventListener('load', () => {
        const sortApp = Vue.createApp({
            template: `
                <draggable :list="documents" item-key="title" @change="onChange">
                <template #item="{ element }">
                    <div class="list-group-item">
                        <input type="hidden" name="document[]" :value="element.id">
                        <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
                        {{ element.title }}
                    </div>
                </template>
                </draggable>

                <div class="saveRow">
                <button type="submit" class="btn btn-primary btnSave" name="sortDocuments">
                    <?= Yii::t('voting', 'settings_sort_save') ?>
                </button>
                </div>`,
            data() {
                return {
                    documents: <?= json_encode($documentsJs) ?>,
                };
            },
            computed: {}
        });
        sortApp.component('draggable', vuedraggable);
        sortApp.config.compilerOptions.whitespace = 'condense';
        sortApp.mount(document.getElementById('sortDocumentsHolder'));

        document.querySelector('.sortDocumentsOpener').addEventListener('click', () => {
            if (document.querySelector('.documentSortingForm').classList.contains('hidden')) {
                document.querySelector('.documentSortingForm').classList.remove('hidden');
                document.querySelector('.btnFileGroupCreate').classList.add('hidden');
                document.querySelectorAll('.fileGroupHolder').forEach(element => {
                    element.classList.add('hidden');
                });
            } else {
                document.querySelector('.documentSortingForm').classList.add('hidden');
                document.querySelector('.btnFileGroupCreate').classList.remove('hidden');
                document.querySelectorAll('.fileGroupHolder').forEach(element => {
                    element.classList.remove('hidden');
                });
            }

        });
    });
</script>
