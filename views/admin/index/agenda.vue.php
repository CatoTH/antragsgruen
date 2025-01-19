<?php

ob_start();
?>
<section class="votingSorting stdSortingWidget">
    {{ agenda }}
    <draggable :list="agenda" item-key="id" @change="onChange">
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
</section>

<?php
$html = ob_get_clean();
?>

<script>
    __setVueComponent('agenda', 'component', 'draggable', vuedraggable);

    __setVueComponent('agenda', 'component', 'agenda-edit-widget', {
        template: <?= json_encode($html) ?>,
        props: ['agenda'],
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
