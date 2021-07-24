import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingBlock {
    private widget;

    constructor(private $element: JQuery) {
        const $vueEl = this.$element.find(".currentVoting");
        const allVotingData = $element.data('voting');
        const data = {
            votings: allVotingData,
            csrf: $("head").find("meta[name=csrf-token]").attr("content") as string,
        };
        console.log("Voting data: ", allVotingData, $vueEl);

        this.widget = new Vue({
            el: $vueEl[0],
            template: `<div class="currentVotings"><voting-block-widget v-for="voting in votings" :voting="voting" :csrf="csrf"></voting-block-widget></div>`,
            data
        });
    }
}
