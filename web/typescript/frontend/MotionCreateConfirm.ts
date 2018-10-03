export class MotionCreateConfirm {
    private $selectors;

    constructor(private $widget: JQuery) {
        this.$selectors = this.$widget.find("input[name=viewMode]");
        this.$selectors.change(this.onModeChanged.bind(this)).trigger("change");
    }

    private onModeChanged() {
        let selected = this.$selectors.filter(":checked").val(),
            $pdf = $(".pdfVersion"),
            $web = $(".webVersion");

        if (selected === 'pdf') {
            $pdf.removeClass('hidden');
            $web.addClass('hidden');

            if (!$pdf.data('initialized')) {
                $pdf.data('initialized', '1');
                $pdf.html($pdf.data('src'));
            }
        } else {
            $pdf.addClass('hidden');
            $web.removeClass('hidden');
        }
    }
}
