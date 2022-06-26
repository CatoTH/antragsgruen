declare var Vue: any;

export class SpeechListEdit {
    private widget;

    constructor(private $element: JQuery) {
        this.widget = new Vue({
            template: `<speech-admin-widget v-bind:queue="queue" v-bind:csrf="csrf"></speech-admin-widget>`,
            data: {
                queue: $element.data('queue'),
                user: $element.data('user'),
                csrf: $("head").find("meta[name=csrf-token]").attr("content") as string,
            }
        }).mount(this.$element.find(".speechAdmin")[0]);
    }
}
