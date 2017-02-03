declare let Clipboard: any;

export class CopyUrlToClipboard {
    constructor($widget: JQuery) {
        let $button = $widget.find("button"),
            clipboard = new Clipboard($button[0]);

        clipboard.on('success', function (e) {
            $widget.find(".form-group").addClass("has-success has-feedback");
            $button.focus();
        });

        clipboard.on('error', function () {
            alert("Could not copy the URL to the clipboard");
        });
    }
}