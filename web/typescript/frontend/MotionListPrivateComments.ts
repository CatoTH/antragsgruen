export class MotionListPrivateComments {
    constructor(private $element: JQuery) {
        const holder = $element[0];
        holder.childNodes.forEach(comment => {
            if (comment.nodeType !== Node.ELEMENT_NODE) {
                return;
            }
            const commentNode = comment as HTMLElement;
            const selector = '.' + commentNode.attributes['data-target-type'].value + 'Row' + commentNode.attributes['data-target-id'].value;
            document.querySelectorAll(selector).forEach(motionRow => {
                const commentHolder = motionRow.querySelector('.privateCommentHolder');
                if (!commentHolder) {
                    return;
                }
                const clonedComment = comment.cloneNode(true) as HTMLElement;
                const link = motionRow.querySelector('.titleLink a');
                if (link) {
                    clonedComment.setAttribute('href', link.getAttribute('href'));
                }

                commentHolder.parentNode.insertBefore(clonedComment, commentHolder);
                commentHolder.parentNode.removeChild(commentHolder);

                $(clonedComment).find("[data-toggle=\"tooltip\"]").tooltip();
            });
        });
    }
}
