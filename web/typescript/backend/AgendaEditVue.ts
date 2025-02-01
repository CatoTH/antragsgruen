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

        this.widget = Vue.createApp({
            template: `<div class="agendaEditHolder">
                <agenda-edit-widget
                    v-model="agenda"
                    ref="agenda-edit-widget"
                ></agenda-edit-widget>
            </div>`,
            data() {
                return {
                    agenda
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
