import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingBlock {
    private widget;

    constructor(private $element: JQuery) {
        const $vueEl = this.$element.find(".currentVoting");
        const data = {
            voting: $element.data('voting'),
            csrf: $("head").find("meta[name=csrf-token]").attr("content") as string,
        };
        this.widget = new Vue({
            el: $vueEl[0],
            template: `
                <voting-block-widget :voting="voting" :csrf="csrf"></voting-block-widget>`,
            data
        });
    }
}
