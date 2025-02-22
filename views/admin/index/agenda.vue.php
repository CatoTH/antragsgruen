<?php

ob_start();
?>
<draggable-plus v-model="list" class="drag-area" :animation="150" :group="disabled ? 'disabled' : 'agenda'" tag="ul" handle=".sortIndicator" @clone="onClone">
    <li v-for="item in list" :key="item.id" class="item" :class="'type_' + item.type">
        <p v-if="item.type == 'item'">
            <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
            <span v-if="!isEditing(item)">{{ item.title }}</span>
            <span v-if="isEditing(item)"><input type="text" v-model="item.title" class="form-control"/></span>
            <button type="button" class="btn btn-link editBtn" title="Bearbeiten" @click="toggleEditing(item)">
                <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
            </button>
        </p>
        <agenda-sorter v-if="item.type == 'item'" v-model="item.children" :disabled="disableChildList" />

        <p v-if="item.type == 'date_separator'">
            <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
            <span v-if="!isEditing(item)">{{ item.title }}</span>
            <span v-if="isEditing(item)"><input type="text" v-model="item.title" class="form-control"/></span>
            <button type="button" class="btn btn-link editBtn" title="Bearbeiten" @click="toggleEditing(item)">
                <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
            </button>
        </p>
    </li>
</draggable-plus>
<div class="adderRow">
    <button type="button" class="btn btn-link adderBtn" @click="addItemRow()">
        <span class="glyphicon glyphicon-add" aria-hidden="true"></span>
        Eintrag hinzufügen
    </button>
    <button type="button" class="btn btn-link adderBtn" @click="addDateSeparatorRow()" v-if="root">
        <span class="glyphicon glyphicon-add" aria-hidden="true"></span>
        Datum hinzufügen
    </button>
</div>
<?php
$html = ob_get_clean();

?>
<script>
    __setVueComponent('agenda', 'component', 'draggable-plus', VueDraggablePlus.VueDraggable);

    __setVueComponent('agenda', 'component', 'agenda-sorter', {
        template: <?= json_encode($html) ?>,
        props: ['modelValue', 'root', 'disabled'],
        data() {
            return {
                editing: [],
                disableChildListExplicitly: false,
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
            },
            disableChildList: function () {
                return this.disabled || this.disableChildListExplicitly;
            }
        },
        methods: {
            onClone: function (evt) {
                this.disableChildListExplicitly = evt.clone.classList.contains("type_date_separator") || this.disabled;
            },
            isEditing: function(item) {
                return this.editing.indexOf(item.id) !== -1;
            },
            toggleEditing: function(item) {
                if (this.editing.indexOf(item.id) === -1) {
                    this.editing.push(item.id);
                } else {
                    this.editing = this.editing.filter(id => id !== item.id);
                }
            },
            addItemRow: function() {
                this.modelValue.push({
                    id: 'NEW',
                    type: 'item',
                    code: null,
                    title: '',
                    time: null,
                    children: [],
                }); // @TODO Open Editing
            },
            addDateSeparatorRow: function() {
                this.modelValue.push({
                    id: 'NEW',
                    type: 'date_separator',
                    code: null,
                    title: '',
                    date: null,
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
    <agenda-sorter v-model="list" :root="true"></agenda-sorter>

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
