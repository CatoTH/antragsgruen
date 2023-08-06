export class MotionMergeAmendmentsConfirm {
    private $sections: JQuery;
    private $newStatus: JQuery;

    constructor(public $widget: JQuery) {
        this.$sections = $widget.find(".motionTextHolder");
        $widget.find("input[name=diffStyle]").on("change", this.onChangeDiffStyle.bind(this)).trigger("change");

        this.initResolutionFunctions();
        this.initVotingFunctions();
    }

    private onChangeDiffStyle() {
        let style = this.$widget.find("input[name=diffStyle]:checked").val();
        if (style == 'diff') {
            this.$sections.find(".fullText").addClass('hidden');
            this.$sections.find(".diffText").removeClass('hidden');
        } else {
            this.$sections.find(".fullText").removeClass('hidden');
            this.$sections.find(".diffText").addClass('hidden');
        }
    }

    private initResolutionFunctions() {
        this.$newStatus = this.$widget.find('.newMotionStatus input');
        this.$newStatus.on('change', () => {
            if (this.$newStatus.filter(':checked').val() === 'motion') {
                this.$widget.find('.newMotionSubstatus').removeClass('hidden');
                this.$widget.find('.newMotionInitiator').addClass('hidden');
            } else {
                this.$widget.find('.newMotionSubstatus').addClass('hidden');
                this.$widget.find('.newMotionInitiator').removeClass('hidden');
            }
        }).trigger('change');
        this.$widget.find("#dateResolutionHolder").datetimepicker({
            locale: $("html").attr('lang'),
            format: 'L'
        });
    }

    private initVotingFunctions() {
        const $closer = $(".votingResultCloser"),
            $opener = $(".votingResultOpener"),
            $inputRows = $(".contentVotingResult, .contentVotingResultComment");
        $opener.on("click", () => {
            $closer.removeClass("hidden");
            $opener.addClass("hidden");
            $inputRows.removeClass("hidden");
        });
        $closer.on("click", () => {
            $closer.addClass("hidden");
            $opener.removeClass("hidden");
            $inputRows.addClass("hidden");
        });
    }
}
