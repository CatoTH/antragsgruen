<?php

/**
 * @var \yii\web\View $this
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

ob_start();
?>
<section aria-labelledby="sortVotingsHeader" class="votingSorting">
    <h2 class="green" id="sortVotingsHeader"><?= Yii::t('voting', 'settings_sort_title') ?></h2>
    <div class="content adminContent">
        <draggable :list="votings" item-key="title" @change="onChange">
            <template #item="{ element }">
                <div class="list-group-item">
                    <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
                    {{ element.title }}
                </div>
            </template>
        </draggable>

        <div class="saveRow">
            <button type="button" @click="saveOrder()" class="btn btn-primary btnSave">
                <?= Yii::t('voting', 'settings_sort_save') ?>
            </button>
        </div>
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
            return {}
        },
        methods: {
            onChange: function () {},
            getSortedIds: function () {
                return this.votings.map(voting => {
                    return voting.id;
                });
            },
            saveOrder: function () {
                this.$emit('sorted', this.getSortedIds());
            },
            setOrder: function (orderVotingIds) { // called by test cases
                const indexedOrder = {};
                orderVotingIds.forEach((votingId, idx) => indexedOrder[votingId.toString()] = idx);
                this.votings = this.votings.sort((voting1, voting2) => {
                    return indexedOrder[voting1.id] - indexedOrder[voting2.id];
                });
            }
        }
    });
</script>
