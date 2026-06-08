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

<script type="module" crossorigin="anonymous">
    import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
    import vuedraggable from "/npm/vuedraggable.js";
    const sortSaveLabel = <?= json_encode(Yii::t('voting', 'settings_sort_save')) ?>;

    const sortApp = createApp({
        render() {
            const Draggable = resolveComponent('draggable');

            return [
                h(Draggable,
                    {
                        list: this.documents,
                        itemKey: 'title',
                        onChange: ($event) => this.onChange($event),
                    },
                    {
                        item: ({ element }) =>
                            h('div', { class: 'list-group-item' }, [
                                h('input', {
                                    type: 'hidden',
                                    name: 'document[]',
                                    value: element.id,
                                }),
                                h('span', {
                                    class: 'glyphicon glyphicon-sort sortIndicator',
                                    'aria-hidden': 'true',
                                }),
                                element.title,
                            ]),
                    }
                ),

                h('div', { class: 'saveRow' },
                    h('button', {
                        type: 'submit',
                        class: 'btn btn-primary btnSave',
                        name: 'sortDocuments',
                    }, sortSaveLabel)
                ),
            ];
        },
        data() {
            return {
                documents: <?= json_encode($documentsJs) ?>,
            };
        },
        methods: {
            onChange() {}
        },
        computed: {}
    });
    sortApp.component('draggable', vuedraggable);
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
</script>
