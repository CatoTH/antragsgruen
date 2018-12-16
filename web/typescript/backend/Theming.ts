class ImageChooser {
    constructor(private $row) {
        const $uploadLabel = $row.find('.uploadCol label .text');
        $row.on('click', '.imageChooserDd a', ev => {
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
        $row.find("input[type=file]").change(() => {
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
    }
}
