import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

export class VotingAdmin {
    private widget;

    constructor(private $element: JQuery) {
        const $vueEl = this.$element.find(".votingAdmin")[0];
        const allVotingData = $element.data('voting');
        console.log("Voting data: ", allVotingData);
        const data = {
            votings: allVotingData,
            csrf: $("head").find("meta[name=csrf-token]").attr("content") as string,
        };
        this.widget = new Vue({
            el: $vueEl,
            template: `<div class="adminVotings">
                <voting-admin-widget v-for="voting in votings" v-bind:voting="voting" v-bind:csrf="csrf"></voting-admin-widget>
            </template>`,
            data
        });
    }
}
