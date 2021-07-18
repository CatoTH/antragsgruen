import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingAdmin {
    private widget;

    constructor(private $element: JQuery) {
        this.widget = new Vue({
            el: this.$element.find(".votingAdmin")[0],
            template: `<voting-admin-widget v-bind:voting="voting" v-bind:csrf="csrf"></voting-admin-widget>`,
            data: {
                voting: $element.data('voting'),
                csrf: $("head").find("meta[name=csrf-token]").attr("content") as string,
            }
        });
    }
}
