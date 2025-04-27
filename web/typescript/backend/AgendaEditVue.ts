declare let Vue: any;

export class AgendaEditVue {
    private widget;
    private element: HTMLElement;

    constructor($element: JQuery) {
        this.element = $element[0];
        this.createVueWidget();
    }

    private createVueWidget() {
        const vueEl = this.element.querySelector(".agendaEdit");
        let agendaWidgetComponent;

        const agenda = JSON.parse(this.element.getAttribute('data-agenda'));
        const motionTypes = JSON.parse(this.element.getAttribute('data-motion-types'));
        const saveAgendaUrl = this.element.getAttribute('data-save-agenda-url');
        const csrf = document.querySelector('head meta[name=csrf-token]').getAttribute('content') as string;

        this.widget = Vue.createApp({
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

        this.widget.config.compilerOptions.whitespace = 'condense';
        window['__initVueComponents'](this.widget, 'agenda');

        agendaWidgetComponent = this.widget.mount(vueEl);

        // Used by tests to control vue-select
        window['agendaWidget'] = agendaWidgetComponent;
    }
}
