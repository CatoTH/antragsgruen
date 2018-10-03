export class MotionMergeAmendmentsConfirm {
    private $sections: JQuery;
    private $newStatus: JQuery;

    constructor(public $widget: JQuery) {
        this.$sections = $widget.find(".motionTextHolder");
        $widget.find("input[name=diffStyle]").change(this.onChangeDiffStyle.bind(this));
        $widget.find("input[name=diffStyle]").first().change();

        this.initResolutionFunctions();
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
                this.$widget.find('.newMotionInitiator').addClass('hidden');
            } else {
                this.$widget.find('.newMotionInitiator').removeClass('hidden');
            }
        }).trigger('change');
        this.$widget.find("#dateResolutionHolder").datetimepicker({
            locale: $("html").attr('lang'),
            format: 'L'
        });
    }
}
