export class Comments {
    constructor(private $widget: JQuery) {
        $widget.on('click', '.replyButton', (ev) => {
            const commentId = $(ev.currentTarget).data('reply-to'),
                $replyTo = $widget.find('.replyTo' + commentId);
            if ($replyTo.hasClass('hidden')) {
                $replyTo.removeClass('hidden');
                $replyTo.find('textarea').trigger('focus');
            } else {
                $replyTo.addClass('hidden');
            }
        });

        $widget.on('change', '.commentNotifications .notisActive', (ev) => {
            const $button = $(ev.currentTarget);
            if ($button.prop('checked')) {
                $button.parents('.commentNotifications').find('select').removeClass('hidden');
            } else {
                $button.parents('.commentNotifications').find('select').addClass('hidden');
            }
        });

        $widget.find('.commentNotifications .notisActive').trigger('change');
    }
}
