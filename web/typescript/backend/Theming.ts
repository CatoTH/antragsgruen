class ImageChooser {
    constructor(private $row) {
        const $uploadLabel = $row.find('.uploadCol label .text');
        $row.on('click', '.imageChooserDd ul a', ev => {
            ev.preventDefault();
            const src = $(ev.currentTarget).find("img").attr("src");
            $row.find('input[type=hidden]').val(src);
            if ($row.find('.logoPreview img').length === 0) {
                $row.find('.logoPreview').prepend('<img src="" alt="">');
            }
            $row.find('.logoPreview img').attr('src', src).removeClass('hidden');
            $uploadLabel.text($uploadLabel.data('title'));
            $row.find("input[type=file]").val('');
        });
        $row.find("input[type=file]").on("change", () => {
            const path = $row.find("input[type=file]").val().split('\\');
            const filename = path[path.length - 1];
            $row.find('input[type=hidden]').val('');
            $row.find(".logoPreview img").addClass('hidden');
            $uploadLabel.text(filename);
        });
    }
}

export class Theming {
    constructor(private $form: JQuery) {
        this.$form.find('.row_image').each((i, el) => {
            new ImageChooser($(el));
        });

        this.$form.on('click', '.btnResetTheme', (ev) => {
            ev.preventDefault();

            const options = {
                title: $(ev.currentTarget).data("confirm-title"),
                message: $(ev.currentTarget).data("confirm-message"),
                inputType: 'radio',
                inputOptions: [
                    {
                        text: $(ev.currentTarget).data("name-classic"),
                        value: 'layout-classic',
                    },
                    {
                        text: $(ev.currentTarget).data("name-dbjr"),
                        value: 'layout-dbjr',
                    }
                ],
                callback: (result) => {
                    if (result) {
                        const $defaults = $('<input type="hidden" name="defaults" value="1">').attr("value", result);
                        this.$form.append('<input type="hidden" name="resetTheme" value="1">');
                        this.$form.append($defaults);
                        this.$form.trigger("submit");
                    }
                }
            } as BootboxPromptOptions; // Typings of Bootbox don't support "message"
            bootbox.prompt(options);
        });
    }
}
