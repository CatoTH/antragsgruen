export class LineNumberHighlighting {
    constructor() {
        let $panel = $(".gotoLineNumerPanel"),
            panelIsOpen = false;
        window.addEventListener('keypress', (ev) => {
            if (!panelIsOpen && ev.charCode >= 48 && ev.charCode <= 57) {
                $panel.addClass("active");
                panelIsOpen = true;
                $panel.find("input[name=lineNumber]").val(ev.key).focus();
            }
        });
        $panel.on('submit', (ev) => {
            ev.preventDefault();
            let lineNumber = $panel.find("input[name=lineNumber]").val();
            $panel.removeClass("active");
            panelIsOpen = false;
            let $lineNumber = $(".lineNumber[data-line-number=" + lineNumber + "]");
            $lineNumber.scrollintoview({top_offset: -100});
            $lineNumber.addClass("highlighted");
            window.setTimeout(() => {
                $lineNumber.addClass("highlighted-active");
            }, 50);
            window.setTimeout(() => {
                $lineNumber.removeClass("highlighted-active");
            }, 2000);
            window.setTimeout(() => {
                $lineNumber.removeClass("highlighted");
            }, 2500);
        });
    }
}