import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class CurrentSpeachList {
    private widget;

    constructor(private $element: JQuery) {
        this.widget = new Vue({
            el: this.$element.find(".speechAdmin")[0],
            template: `<speech-admin-widget v-bind:queue="queue" v-bind:csrf="csrf"></speech-admin-widget>`,
            data: {
                queue: $element.data('queue'),
                user: $element.data('user'),
                csrf: $('input[name=_csrf]').val() as string,
            }
        });
    }
}
