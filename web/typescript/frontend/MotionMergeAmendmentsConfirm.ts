export class MotionMergeAmendmentsConfirm {
    private $sections: JQuery;

    constructor(public $widget: JQuery) {
        this.$sections = $widget.find(".motionTextHolder");
        $widget.find("input[name=diffStyle]").change(this.onChangeDiffStyle.bind(this));
        $widget.find("input[name=diffStyle]").first().change();
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
}
