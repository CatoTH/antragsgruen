<?php

ob_start();
?>
<draggable-plus v-model="list" class="drag-area" :animation="150" group="agenda" tag="ul" handle=".sortIndicator" @update="onUpdate">
    <li v-for="item in list" :key="item.id" class="item">
        <p>
            <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
            <span v-if="!editing">{{ item.title }}</span>
            <span v-if="editing"><input type="text" v-model="item.title" class="form-control"/></span>
            <button type="button" class="btn btn-link editBtn" title="Bearbeiten" @click="toggleEditing()">
                <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
            </button>
        </p>
        <agenda-sorter v-model="item.children" />
    </li>
</draggable-plus>
<div class="adderRow">
    <button type="button" class="btn btn-link adderBtn" @click="addRow()">
        <span class="glyphicon glyphicon-add" aria-hidden="true"></span>
        Hinzufügen
    </button>
</div>
<?php
$html = ob_get_clean();

?>
<script>
    __setVueComponent('agenda', 'component', 'draggable-plus', VueDraggablePlus.VueDraggable);

    __setVueComponent('agenda', 'component', 'agenda-sorter', {
        template: <?= json_encode($html) ?>,
        props: ['modelValue'],
        data() {
            return {
                editing: false,
            }
        },
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
            },
            toggleEditing: function() {
                this.editing = !this.editing;
            },
            addRow: function() {
                console.log(this.modelValue);
                this.modelValue.push({
                    id: 'NEW',
                    title: '',
                    children: [],
                }); // @TODO Open Editing
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
