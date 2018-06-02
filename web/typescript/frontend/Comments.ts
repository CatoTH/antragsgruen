export class Comments {
    constructor(private $widget: JQuery) {
        $widget.on('click', '.replyButton', (ev) => {
            const commentId = $(ev.currentTarget).data('reply-to'),
                $replyTo = $widget.find('.replyTo' + commentId);
            if ($replyTo.hasClass('hidden')) {
                $replyTo.removeClass('hidden');
                $replyTo.find('textarea').focus();
            } else {
                $replyTo.addClass('hidden');
            }
        });
    }
}