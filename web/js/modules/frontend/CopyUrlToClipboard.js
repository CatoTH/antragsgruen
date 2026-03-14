// @ts-check

export function copyUrlToClipboard(widget) {
    const $widget = $(widget),
        $button = $widget.find("button"),
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
