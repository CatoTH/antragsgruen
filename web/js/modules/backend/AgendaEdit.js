// @ts-check

import { createApp } from '/npm/vue.esm-browser.prod.js';
import translateDirective from "/js/vue/Translate.vue.js";
import AgendaEditItemRow from '/js/vue/agenda/AgendaEditItemRow.js';
import AgendaSorter from '/js/vue/agenda/AgendaSorter.js';
import DatetimePicker from '/js/vue/DatetimePicker.js';
import AgendaEditWidget from '/js/vue/agenda/AgendaEditWidget.js';
import { VueDraggable } from '/npm/vue-draggable-plus.js';

export class AgendaEdit {
    /** @type {import('vue').App} */ widget;
    /** @type {HTMLElement} */element;

    constructor(element) {
        this.element = element;
        this.createVueWidget();
    }

    createVueWidget() {
        const vueEl = this.element.querySelector(".agendaEdit");
        let agendaWidgetComponent;

        const agenda = JSON.parse(this.element.getAttribute('data-agenda'));
        const motionTypes = JSON.parse(this.element.getAttribute('data-motion-types'));
        const saveAgendaUrl = this.element.getAttribute('data-save-agenda-url');
        const speechAdminUrl = this.element.getAttribute('data-speech-admin-url');
        const locale = this.element.getAttribute('data-locale');
        const csrf = document.querySelector('head meta[name=csrf-token]').getAttribute('content');

        this.widget = createApp({
            template: `<div class="agendaEditHolder">
                <agenda-edit-widget
                    v-model="agenda"
                    :motionTypes="motionTypes"
                    ref="agenda-edit-widget"
                    @saveAgenda="onSaveAgenda()"
                ></agenda-edit-widget>
            </div>`,
            data() {
                return {
                    agenda,
                    motionTypes
                }
            },
            methods: {
                onSaveAgenda() {
                    const widget = this;

                    $.ajax({
                        url: saveAgendaUrl,
                        type: "POST",
                        data: JSON.stringify(this.agenda),
                        processData: false,
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        headers: {"X-CSRF-Token": csrf},
                        success: data => {
                            if (data.error) {
                                bootbox.alert(data.error);
                            } else {
                                widget.agenda = data;
                                widget.$refs['agenda-edit-widget'].onSaved();
                            }
                        }
                    }).catch(function (err) {
                        bootbox.alert(err.responseText);
                    })
                }
            }
        });

        DatetimePicker.setLocale(locale);
        AgendaEditItemRow.setSpeechAdminUrl(speechAdminUrl);

        this.widget.directive('t', translateDirective);
        this.widget.component('agenda-edit-widget', AgendaEditWidget);
        this.widget.component('agenda-sorter', AgendaSorter);
        this.widget.component('agenda-edit-item-row', AgendaEditItemRow);
        this.widget.component('v-datetime-picker', DatetimePicker);
        this.widget.component('draggable-plus', VueDraggable);

        agendaWidgetComponent = this.widget.mount(vueEl);

        // Used by tests to control vue-select
        window['agendaWidget'] = agendaWidgetComponent;
    }
}
