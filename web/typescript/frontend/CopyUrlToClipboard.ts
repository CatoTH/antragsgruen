declare let ClipboardJS: any;

export class CopyUrlToClipboard {
    constructor($widget: JQuery) {
        let $button = $widget.find("button"),
            clipboard = new ClipboardJS($button[0]);

        $widget.find(".clipboard-done").addClass("hidden");
        clipboard.on('success', () => {
            $widget.find(".form-group").addClass("has-success has-feedback");
            $widget.find(".clipboard-done").removeClass("hidden");
            $button.trigger("focus");
        });

        clipboard.on('error', () => {
            alert("Could not copy the URL to the clipboard");
        });
    }
}
