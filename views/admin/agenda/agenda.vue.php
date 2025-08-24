<?php

declare(strict_types=1);

use app\components\Tools;
use app\components\UrlHelper;

$simpleDeadlineMotions = '';
$locale = Tools::getCurrentDateLocale();

ob_start();
?>
<div class="infoRow">
    <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
    <v-datetime-picker v-model="modelValue.time" type="time" :locale="locale" v-if="showTime" />

    <input type="text" v-model="modelValue.code" :placeholder="codeBase" class="form-control codeCol"/>
    <input type="text" v-model="modelValue.title" class="form-control titleCol"/>

    <select class="stdDropdown motionTypeCol" @change="onMotionTypeChange($event)">
        <option>-</option>
        <option v-for="motionType in motionTypes" :value="motionType.id" :selected="isMotionTypeSelected(motionType)">{{ motionType.title }}</option>
    </select>

    <div class="dropdown extraSettings">
        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
            <span class="glyphicon glyphicon-wrench" aria-label="<?= Yii::t('admin', 'agenda_move_aria') ?>"></span>
            <span class="caret" aria-hidden="true"></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right">
            <li class="checkbox inProposedProcedures" v-if="hasProposedProcedure()">
                <label>
                    <input type="checkbox" v-model="modelValue.settings.in_proposed_procedures">
                    <?= Yii::t('con', 'agenda_pp') ?>
                </label>
            </li>
            <li class="checkbox hasSpeakingList">
                <label>
                    <input type="checkbox" v-model="modelValue.settings.has_speaking_list">
                    <?= Yii::t('con', 'agenda_speaking') ?>
                </label>
            </li>
        </ul>
    </div>

    <div class="addLinkHolder">
        <a v-for="list in modelValue.settings.speaking_lists" :href="speakingAdminLink(list)"
           title="<?= Yii::t('admin', 'agenda_speeking_link') ?>">
            <span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>
            <span class="sr-only"><?= Yii::t('admin', 'agenda_speeking_link') ?></span>
        </a>

        <button class="btn btn-link btnDelete" type="button" @click="removeItem()" title="<?= Yii::t('con', 'agenda_del') ?>">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
        </button>
    </div>
</div>
<?php
$html = ob_get_clean();
?>
<script>
    const speechAdminUrlTemplate = <?= json_encode(UrlHelper::createUrl(['/consultation/admin-speech', 'queue' => 'QUEUE'])) ?>;
    __setVueComponent('agenda', 'component', 'agenda-edit-item-row', {
        template: <?= json_encode($html) ?>,
        props: {
            modelValue: { type: Array },
            locale: { type: String },
            codeBase: { type: String },
            motionTypes: { type: Array },
            showTime: { type: Boolean }
        },
        data() {
            return {}
        },
        methods: {
            hasProposedProcedure: function() {
                return this.motionTypes.filter(type => type.has_proposed_procedure).length > 0;
            },
            isMotionTypeSelected(motionType) {
                return this.modelValue.settings.motion_types.indexOf(motionType.id) !== -1;
            },
            onMotionTypeChange(event) {
                this.modelValue.settings.motion_types = (event.target.value ? [parseInt(event.target.value)] : null);
            },
            removeItem: function() {
                this.$emit('remove');
            },
            speakingAdminLink: function(list) {
                return speechAdminUrlTemplate.replace(/QUEUE/, list);
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
    <li v-for="(item, itemIndex) in list" :key="item.id" class="item" :class="'type_' + item.type + ' item_' + item.id">
        <agenda-edit-item-row
            v-if="item.type == 'item'"
            v-model="item" :motionTypes="motionTypes"
            :locale="locale" :codeBase="getCodeBase(itemIndex)" :showTime="showTime"
            @remove="removeItem(item)"
        />
        <agenda-sorter v-if="item.type == 'item'" v-model="item.children" :motionTypes="motionTypes" :disabled="disableChildList" :showTime="showTime" />

        <div v-if="item.type == 'date_separator'" class="infoRow" @remove="removeItem(item)">
            <span class="glyphicon glyphicon-sort sortIndicator" aria-hidden="true"></span>
            <v-datetime-picker v-model="item.date" type="date" :locale="locale" />

            <div class="deleteHolder">
                <button class="btn btn-link btnDelete" type="button" @click="removeItem(item)" title="<?= Yii::t('con', 'agenda_del') ?>">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </li>
</draggable-plus>
<div class="adderRow">
    <button type="button" class="btn btn-link adderBtn" @click="addItemRow()">
        <span class="glyphicon glyphicon-add" aria-hidden="true"></span>
        <?= Yii::t('admin', 'agenda_add_item') ?>
    </button>
    <button type="button" class="btn btn-link adderBtn" @click="addDateSeparatorRow()" v-if="root">
        <span class="glyphicon glyphicon-add" aria-hidden="true"></span>
        <?= Yii::t('admin', 'agenda_add_date') ?>
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
                    window.setTimeout(() => {
                        this.recalculateCodeBases(value);
                    }, 1);
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
                        in_proposed_procedures: false,
                        has_speaking_list: false,
                        motion_types: [],
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
                    settings: {
                        in_proposed_procedures: false,
                        has_speaking_list: false,
                        motion_types: [],
                    },
                });
                this.recalculateCodeBases(this.modelValue)
            },
            removeItem: function(item) {
                const newValues = this.modelValue.filter(it => it !== item);
                this.$emit('update:modelValue', newValues);
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
                    } else if (lastValue.match(/^[a-z]\.?$/i)) {
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
    <div class="settings">
        <label>
            <input type="checkbox" v-model="showTime" :disabled="anyItemHasTime" class="showTimeSelector">
            <?= Yii::t('admin', 'agenda_show_times') ?>
        </label>
    </div>
    <agenda-sorter v-model="list" :motionTypes="motionTypes" :root="true" :showTime="showTime"></agenda-sorter>

    <div class="saveRow" :class="{saving: saving, saved: saved, savable: !saving && !saved}">
        <div class="savable">
            <button type="button" @click="saveAgenda()" class="btn btn-primary btnSave">
                <?= Yii::t('voting', 'settings_sort_save') ?>
            </button>
        </div>
        <div class="saving">
            Saving...
        </div>
        <div class="saved">
            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span> Saved
        </div>
    </div>
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
            },
            anyItemHasTime: function () {
                const checkItemRec = function(items) {
                    for (let item in items) {
                        if (items[item].time) {
                            return true;
                        }
                        if (checkItemRec(items[item].children)) {
                            return true;
                        }
                    }
                    return false;
                };
                return checkItemRec(this.modelValue);
            }
        },
        data() {
            return {
                showTime: false,
                saving: false,
                saved: false,
            }
        },
        methods: {
            saveAgenda: function() {
                this.saving = true;
                this.$emit('save-agenda');
            },
            onSaved: function() {
                this.saving = false;
                this.saved = true;
                setTimeout(() => {
                    this.saved = false;
                }, 2000);
            },
            getAgendaTest: function() {
                return this.modelValue;
            },
            setAgendaTest: function(value) {
                this.$emit('update:modelValue', value);
            }
        },
        created: function () {
            this.showTime = this.anyItemHasTime;
        }
    });
</script>
