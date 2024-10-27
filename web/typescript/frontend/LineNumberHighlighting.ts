export class LineNumberHighlighting {
    constructor() {
        let $panel = $(".gotoLineNumerPanel"),
            $lineInput = $panel.find("input[name=lineNumber]"),
            panelIsOpen = false;
        window.addEventListener('keydown', (ev) => {
            if (!panelIsOpen && ev.key >= '0' && ev.key <= '9') {
                let $target = $(ev.target);
                if ($target.is('input, textarea, div.texteditor, .cke_editable') || $target.parents('input, textarea, div.texteditor, .cke_editable').length > 0) {
                    // Typing in an input field, like comments
                    return;
                }

                $panel.addClass("active");
                panelIsOpen = true;
                $lineInput.trigger("focus");
                window.setTimeout(() => {
                    $lineInput.val(ev.key);
                }, 1);
            }
            if (panelIsOpen) {
                $panel.find('.lineNumberNotFound').addClass('hidden');
            }
        });
        $panel.on('submit', (ev) => {
            ev.preventDefault();
            let lineNumber = $lineInput.val();
            if (lineNumber === '') {
                $panel.removeClass("active");
                panelIsOpen = false;
                return;
            }

            let $lineNumber = $(".lineNumber[data-line-number=" + lineNumber + "]");
            if ($lineNumber.length === 0) {
                $panel.find('.lineNumberNotFound').removeClass('hidden');
                return;
            }
            $panel.removeClass("active");
            panelIsOpen = false;

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
