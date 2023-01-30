declare let Vue: any;

export class SpeechListEdit {
    private widget;

    constructor(private $element: JQuery) {
        this.widget = Vue.createApp({
            template: `<speech-admin-widget :initQueue="queue" :csrf="csrf"></speech-admin-widget>`,
            data() { return {
                queue: $element.data('queue'),
                user: $element.data('user'),
                csrf: $("head").find("meta[name=csrf-token]").attr("content") as string,
            } }
        });

        this.widget.config.compilerOptions.whitespace = 'condense';
        window['__initVueComponents'](this.widget, 'speech');
        this.widget.mount(this.$element.find(".speechAdmin")[0]);
    }
}
