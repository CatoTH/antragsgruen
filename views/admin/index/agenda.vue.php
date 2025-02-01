<style>
    .drag-area {
        min-height: 50px;
        outline: 1px dashed;
        display: block;
        pointer-events: all;
    }
    .item {
        display: block;
        list-style: none;
        margin: 0;
        padding: 5px;
    }
</style>

<?php

ob_start();
?>
<draggable-plus v-model="list" class="drag-area" :animation="150" group="agenda" tag="ul" handle=".sortIndicator" @update="onUpdate">
    <li v-for="item in list" :key="item.id" class="item">
        <p>
            <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
            {{ item.title }}
        </p>
        <agenda-sorter v-model="item.children" />
    </li>
</draggable-plus>
<?php
$html = ob_get_clean();

?>
<script>
    __setVueComponent('agenda', 'component', 'draggable-plus', VueDraggablePlus.VueDraggable);

    __setVueComponent('agenda', 'component', 'agenda-sorter', {
        template: <?= json_encode($html) ?>,
        props: ['modelValue'],
        computed: {
            list: {
                get: function () {
                    return this.modelValue;
                },
                set: function (value) {
                    this.$emit('update:modelValue', value);
                }
            }
        },
        methods: {
            onUpdate: function() {
                console.log("onUpdate", arguments);
            }
        }
    });
</script>

<?php
ob_start();
?>
<section class="votingSorting stdSortingWidget">
    <agenda-sorter v-model="list"></agenda-sorter>

    <pre>{{ list }}</pre>


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
    __setVueComponent('agenda', 'component', 'agenda-edit-widget', {
        template: <?= json_encode($html) ?>,
        props: ['modelValue'],
        computed: {
            list: {
                get: function () {
                    return this.modelValue;
                },
                set: function (value) {
                    this.$emit('update:modelValue', value);
                }
            }
        },
        data() {
            return {

            }
        },
        watch: {
        },
        methods: {
            onChange: function () {
            }
        }
    });
</script>
