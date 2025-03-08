<?php

declare(strict_types=1);

use app\components\Tools;

$simpleDeadlineMotions = '';
$locale = Tools::getCurrentDateLocale();

ob_start();
?>
<draggable-plus v-model="list" class="drag-area" :animation="150" :group="disabled ? 'disabled' : 'agenda'" tag="ul" handle=".sortIndicator" @clone="onClone">
    <li v-for="item in list" :key="item.id" class="item" :class="'type_' + item.type">
        <div v-if="item.type == 'item'" class="infoRow">
            <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
            <span v-if="!isEditing(item)">{{ item.time }} {{ item.title }}</span>
            <v-datetime-picker v-if="isEditing(item)" v-model="item.time" type="time" :locale="locale" />
            <input type="text" v-if="isEditing(item)" v-model="item.title" class="form-control"/>

            <button type="button" class="btn btn-link editBtn" title="Bearbeiten" @click="toggleEditing(item)">
                <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
            </button>
        </div>
        <agenda-sorter v-if="item.type == 'item'" v-model="item.children" :disabled="disableChildList" />

        <div v-if="item.type == 'date_separator'" class="infoRow">
            <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
            <span v-if="!isEditing(item)">{{ item.date }}</span>
            <div v-if="isEditing(item)">
                <v-datetime-picker v-model="item.date" type="date" :locale="locale" />
            </div>
            <button type="button" class="btn btn-link editBtn" title="Bearbeiten" @click="toggleEditing(item)">
                <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
            </button>
        </div>
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
    let locale = <?=  json_encode($locale) ?>;

    __setVueComponent('agenda', 'component', 'draggable-plus', VueDraggablePlus.VueDraggable);

    __setVueComponent('agenda', 'component', 'agenda-sorter', {
        template: <?= json_encode($html) ?>,
        props: {
            modelValue: {
                type: Array
            },
            root: {
                type: Boolean
            },
            disabled: {
                type: Boolean
            },
            showTime: {
                type: Boolean
            }
        },
        data() {
            return {
                locale,
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
<section class="agendaEditWidget stdSortingWidget">
    <div class="settings" style="text-align: right;">
        <label>
            <input type="checkbox" v-model="showTime"> Zeit anzeigen
        </label>
    </div>
    <agenda-sorter v-model="list" :root="true" :showTime="showTime"></agenda-sorter>

    <div class="saveRow">
        <button type="button" @click="saveAgenda()" class="btn btn-primary btnSave">
            <?= Yii::t('voting', 'settings_sort_save') ?>
        </button>
    </div>

    <pre>{{ list }}</pre>
</section>

<?php
$html = ob_get_clean();
?>

<script>
    __setVueComponent('agenda', 'component', 'agenda-edit-widget', {
        template: <?= json_encode($html) ?>,
        props: {
            modelValue: {
                type: Array
            }
        },
        computed: {
            list: {
                get: function () {
                    return this.modelValue;
                },
                set: function (value) {
                    console.log("set", value)
                    this.$emit('update:modelValue', value);
                }
            }
        },
        data() {
            const anyItemHasTime = function(items) {
                for (let item in items) {
                    if (items[item].time) {
                        return true;
                    }
                    if (anyItemHasTime(items[item].children)) {
                        return true;
                    }
                }
                return false;
            };

            return {
                showTime: anyItemHasTime(this.modelValue)
            }
        },
        watch: {
        },
        methods: {
            saveAgenda: function() {
                this.$emit('save-agenda');
            }
        }
    });
</script>
