<?php

/**
 * @var \yii\web\View $this
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;

ob_start();
?>
<section aria-labelledby="sortVotingsHeader" class="votingSorting stdSortingWidget">
    <h2 class="green" id="sortVotingsHeader"><?= Yii::t('voting', 'settings_sort_title') ?></h2>
    <div class="content adminContent">
        <draggable :list="votingCache" item-key="id" @change="onChange">
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
            return {
                votingCache: null,
                votingCachedIds: null,
            }
        },
        watch: {
            votings: {
                handler(votingArr) {
                    // We need to prevent reloads in the outer component to reset the sorting - unless there is a significant change.
                    const ids = votingArr.map(vot => vot.id).join("-");
                    if (this.votingCachedIds !== ids) {
                        this.votingCachedIds = ids;
                        this.votingCache = votingArr;
                    }
                },
                immediate: true
            }
        },
        methods: {
            onChange: function () {},
            getSortedIds: function () {
                return this.votingCache.map(voting => {
                    return voting.id;
                });
            },
            saveOrder: function () {
                this.$emit('sorted', this.getSortedIds());
            },
            setOrder: function (orderVotingIds) { // called by test cases
                const indexedOrder = {};
                orderVotingIds.forEach((votingId, idx) => indexedOrder[votingId.toString()] = idx);
                this.votingCache = this.votingCache.sort((voting1, voting2) => {
                    return indexedOrder[voting1.id] - indexedOrder[voting2.id];
                });
            }
        }
    });
</script>
