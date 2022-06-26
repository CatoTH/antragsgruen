declare var Vue: any;

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
            this.widget = new Vue({
                template: `
                    <speech-user-full-list-widget :queue="queue" :user="user" :csrf="csrf" :title="title"></speech-user-full-list-widget>`,
                data
            }).mount($vueEl[0]);
        } else if ($element.hasClass('currentSpeechInline')) {
            this.widget = new Vue({
                template: `
                    <speech-user-inline-widget :queue="queue" :user="user" :csrf="csrf" :title="title"></speech-user-inline-widget>`,
                data
            }).mount($vueEl[0]);
        } else {
            this.widget = new Vue({
                template: `
                    <speech-user-footer-widget :queue="queue" :user="user" :csrf="csrf" :title="title" :adminUrl="adminUrl"></speech-user-footer-widget>`,
                data
            }).mount($vueEl[0]);
        }
    }
}
