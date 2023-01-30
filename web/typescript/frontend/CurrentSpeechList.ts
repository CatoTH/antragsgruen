declare let Vue: any;

export class CurrentSpeechList {
    private widget;

    constructor(private $element: JQuery) {
        const $vueEl = this.$element.find(".currentSpeechList");
        const data = {
            queue: $element.data('queue'),
            user: $element.data('user'),
            csrf: $("head").find("meta[name=csrf-token]").attr("content") as string,
            title: $element.data('title'),
            adminUrl: $element.data('admin-url'),
        };
        if ($element.hasClass('currentSpeechFullPage')) {
            this.widget = Vue.createApp({
                template: `
                    <speech-user-full-list-widget :initQueue="queue" :user="user" :csrf="csrf" :title="title"></speech-user-full-list-widget>`,
                data() { return data }
            });
        } else if ($element.hasClass('currentSpeechInline')) {
            this.widget = Vue.createApp({
                template: `
                    <speech-user-inline-widget :initQueue="queue" :user="user" :csrf="csrf" :title="title"></speech-user-inline-widget>`,
                data() { return data }
            });
        } else {
            this.widget = Vue.createApp({
                template: `
                    <speech-user-footer-widget :initQueue="queue" :user="user" :csrf="csrf" :title="title" :adminUrl="adminUrl"></speech-user-footer-widget>`,
                data() { return data }
            });
        }

        this.widget.config.compilerOptions.whitespace = 'condense';
        window['__initVueComponents'](this.widget, 'speech');
        this.widget.mount($vueEl[0]);
    }
}
