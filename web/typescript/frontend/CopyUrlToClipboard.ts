declare let Clipboard: any;

export class CopyUrlToClipboard {
    constructor($widget: JQuery) {
        let $button = $widget.find("button"),
            clipboard = new Clipboard($button[0]);

        $widget.find(".clipboard-done").addClass("hidden");
        clipboard.on('success', function (e) {
            $widget.find(".form-group").addClass("has-success has-feedback");
            $widget.find(".clipboard-done").removeClass("hidden");
            $button.focus();
        });

        clipboard.on('error', function () {
            alert("Could not copy the URL to the clipboard");
        });
    }
}