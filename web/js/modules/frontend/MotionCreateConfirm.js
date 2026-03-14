// @ts-check

export class MotionCreateConfirm {
    /** @type {JQuery} $selectors */

    /**
     * @param {HTMLElement} widget
     */
    constructor(widget) {
        this.$selectors = $(widget).find("input[name=viewMode]");
        this.$selectors.on("change", this.onModeChanged.bind(this)).trigger("change");
    }

    onModeChanged() {
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
