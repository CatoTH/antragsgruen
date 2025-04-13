<?php

declare(strict_types=1);

use app\components\Tools;

$simpleDeadlineMotions = '';
$locale = Tools::getCurrentDateLocale();

ob_start();
?>
<div class="infoRow">
    <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
    <span v-if="!isEditing">{{ modelValue.time }} {{ modelValue.title }}</span>
    <v-datetime-picker v-if="isEditing" v-model="modelValue.time" type="time" :locale="locale" />

    <input type="text" v-if="isEditing" v-model="modelValue.code" :placeholder="codeBase" class="form-control codeCol"/>
    <input type="text" v-if="isEditing" v-model="modelValue.title" class="form-control titleCol"/>


    <select class="stdDropdown motionTypeCol" @change="onMotionTypeChange($event)">
        <option>-</option>
        <option v-for="motionType in motionTypes" :value="motionType.id" :selected="isMotionTypeSelected(motionType)">{{ motionType.title }}</option>
    </select>

    <div class="dropdown extraSettings" v-if="isEditing">
        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
            <span class="glyphicon glyphicon-wrench"></span>
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
            <li class="checkbox inProposedProcedures">
                <label>
                    <input type="checkbox" v-model="modelValue.settings.inProposedProcedures">
                    <?= Yii::t('con', 'agenda_pp') ?>
                </label>
            </li>
            <li class="checkbox hasSpeakingList">
                <label>
                    <input type="checkbox" v-model="modelValue.settings.hasSpeakingList">
                    <?= Yii::t('con', 'agenda_speaking') ?>
                </label>
            </li>
        </ul>
    </div>

    <button type="button" class="btn btn-link editBtn" title="Bearbeiten" @click="isEditing = !isEditing">
        <span class="glyphicon glyphicon-wrench" aria-hidden="true"></span>
    </button>
</div>
<?php
$html = ob_get_clean();
?>
<script>
    __setVueComponent('agenda', 'component', 'agenda-edit-item-row', {
        template: <?= json_encode($html) ?>,
        props: {
            modelValue: { type: Array },
            locale: { type: String },
            codeBase: { type: String },
            motionTypes: { type: Array }
        },
        data() {
            return {
                isEditing: true,
            }
        },
        computed: {
        },
        methods: {
            isMotionTypeSelected(motionType) {
                return this.modelValue.settings.motionTypes.indexOf(motionType.id) !== -1;
            },
            onMotionTypeChange(event) {
                this.modelValue.settings.motionTypes = (event.target.value ? [parseInt(event.target.value)] : null);
            }
        },
        mounted: function () {
            this.$el.querySelectorAll(".dropdown-menu .checkbox").forEach((el) => {
                el.addEventListener("click", ev => ev.stopPropagation());
            });
        }
    });
</script>
<?php

ob_start();
?>
<draggable-plus v-model="list" class="drag-area" :animation="150" :group="disabled ? 'disabled' : 'agenda'" tag="ul" handle=".sortIndicator" @clone="onClone">
    <li v-for="(item, itemIndex) in list" :key="item.id" class="item" :class="'type_' + item.type">
        <agenda-edit-item-row v-if="item.type == 'item'" v-model="item" :motionTypes="motionTypes" :locale="locale" :codeBase="getCodeBase(itemIndex)" />
        <agenda-sorter v-if="item.type == 'item'" v-model="item.children" :motionTypes="motionTypes" :disabled="disableChildList" />

        <div v-if="item.type == 'date_separator'" class="infoRow">
            <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
            <!--
            <span v-if="!isEditing(item)">{{ item.date }}</span>
            <div v-if="isEditing(item)">
                <v-datetime-picker v-model="item.date" type="date" :locale="locale" />
            </div>
            -->
            <v-datetime-picker v-model="item.date" type="date" :locale="locale" />
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
            modelValue: { type: Array },
            motionTypes: { type: Array },
            root: { type: Boolean },
            disabled: { type: Boolean },
            showTime: { type: Boolean }
        },
        data() {
            return {
                locale,
                disableChildListExplicitly: false,
                calculatedCodes: null,
            }
        },
        watch: {
            modelValue: {
                handler(oldValue, newValue) {
                    this.recalculateCodeBases(newValue);
                },
                deep: true
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
            addItemRow: function() {
                this.modelValue.push({
                    id: null,
                    type: 'item',
                    code: null,
                    title: '',
                    time: null,
                    children: [],
                    settings: {
                        inProposedProcedures: false,
                        hasSpeakingList: false,
                        motionTypes: [],
                    },
                });
                this.recalculateCodeBases(this.modelValue)
            },
            addDateSeparatorRow: function() {
                this.modelValue.push({
                    id: null,
                    type: 'date_separator',
                    code: null,
                    title: '',
                    date: null,
                    children: [],
                });
                this.recalculateCodeBases(this.modelValue)
            },
            recalculateCodeBases: function(values) {
                this.calculatedCodes = [];
                let lastValue = null;
                values.forEach((value, idx) => {
                    if (value.type !== 'item') {
                        this.calculatedCodes[idx] = null;
                        return;
                    }

                    if (value.code !== null && value.code !== '') {
                        this.calculatedCodes[idx] = value.code;
                        lastValue = value.code;
                        return;
                    }

                    if (lastValue === null) {
                        this.calculatedCodes[idx] = "1.";
                    } else if (lastValue.match(/^[a-y]\.$/i)) {
                        this.calculatedCodes[idx] = String.fromCharCode(lastValue.charCodeAt(0) + 1) + ".";
                    } else {
                        let strWithoutSeparator = (lastValue.substr(-1) === '.' ? lastValue.substr(0, lastValue.length - 1) : lastValue);
                        let matches = strWithoutSeparator.match(/^(.*[^0-9])?([0-9]*)?$/),
                            nonNumeric = (typeof (matches[1]) == 'undefined' ? '' : matches[1]),
                            numeric = parseInt(matches[2] == '' ? '1' : matches[2]);
                        this.calculatedCodes[idx] = nonNumeric + ++numeric + ".";
                    }
                    lastValue = this.calculatedCodes[idx];
                });
            },
            getCodeBase: function(itemIndex) {
                return this.calculatedCodes[itemIndex];
            }
        },
        created: function () {
            this.recalculateCodeBases(this.modelValue)
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
    <agenda-sorter v-model="list" :motionTypes="motionTypes" :root="true" :showTime="showTime"></agenda-sorter>

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
            modelValue: { type: Array },
            motionTypes: { type: Array }
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
        methods: {
            saveAgenda: function() {
                this.$emit('save-agenda');
            }
        }
    });
</script>
