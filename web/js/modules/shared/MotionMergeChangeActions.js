export class MotionMergeChangeActions {
    static removeEmptyParagraphs() {
        $('.texteditor').each((i, el) => {
            if (el.childNodes.length === 0) {
                $(el).remove();
            }
        });
    }

    /**
     * @param {HTMLElement} node
     * @param {function} onFinished
     */
    static accept(node, onFinished = null) {
        let $node = $(node);
        if ($node.hasClass("ice-ins")) {
            MotionMergeChangeActions.insertAccept(node, onFinished);
        }
        if ($node.hasClass("ice-del")) {
            MotionMergeChangeActions.deleteAccept(node, true, onFinished);
        }
    }

    /**
     * @param {HTMLElement} node
     * @param {function} onFinished
     */
    static reject(node, onFinished = null) {
        let $node = $(node);
        if ($node.hasClass("ice-ins")) {
            MotionMergeChangeActions.insertReject($node, onFinished);
        }
        if ($node.hasClass("ice-del")) {
            MotionMergeChangeActions.deleteReject($node, onFinished);
        }
    }

    /**
     * @param {JQuery} $node
     * @param {function} onFinished
     */
    static insertReject($node, onFinished = null) {
        let $removeEl,
            name = $node[0].nodeName.toLowerCase();
        if (name === 'li') {
            $removeEl = $node.parent();
        } else {
            $removeEl = $node;
        }
        if (name === 'ul' || name === 'ol' || name === 'li' || name === 'blockquote' || name === 'pre' || name === 'p') {
            $removeEl.css("overflow", "hidden").height($removeEl.height());
            $removeEl.animate({"height": "0"}, 250, () => {
                $removeEl.remove();
                $(".collidingParagraph:empty").remove();
                MotionMergeChangeActions.removeEmptyParagraphs();
                if (onFinished) onFinished();
            });
        } else {
            $removeEl.remove();
            if (onFinished) onFinished();
        }
    }

    /**
     * @param {HTMLElement} node
     * @param {function} onFinished
     */
    static insertAccept(node, onFinished = null) {
        let $this = $(node);
        $this.removeClass("ice-cts ice-ins appendHint moved");
        $this.removeAttr("data-moving-partner data-moving-partner-id data-moving-partner-paragraph data-moving-msg");
        if (node.nodeName.toLowerCase() === 'ul' || node.nodeName.toLowerCase() === 'ol') {
            $this.children().removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint");
        }
        if (node.nodeName.toLowerCase() === 'li') {
            $this.parent().removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint");
        }
        if (node.nodeName.toLowerCase() === 'ins') {
            $this.replaceWith($this.html());
        }
        if (onFinished) onFinished();
    }

    /**
     * @param {JQuery} $node
     * @param {function} onFinished
     */
    static deleteReject($node, onFinished = null) {
        $node.removeClass("ice-cts ice-del appendHint");
        $node.removeAttr("data-moving-partner data-moving-partner-id data-moving-partner-paragraph data-moving-msg");
        let nodeName = $node[0].nodeName.toLowerCase();
        if (nodeName === 'ul' || nodeName === 'ol') {
            $node.children().removeClass("ice-cts").removeClass("ice-del").removeClass("appendHint");
        }
        if (nodeName === 'li') {
            $node.parent().removeClass("ice-cts").removeClass("ice-del").removeClass("appendHint");
        }
        if (nodeName === 'del') {
            $node.replaceWith($node.html());
        }
        if (onFinished) onFinished();
    }

    /**
     * @param {HTMLElement} node
     * @param {boolean} delayed
     * @param {function} onFinished
     */
    static deleteAccept(node, delayed, onFinished = null) {
        let name = node.nodeName.toLowerCase(),
            $removeEl;
        if (name === 'li') {
            $removeEl = $(node).parent();
        } else {
            $removeEl = $(node);
        }

        if (name === 'ul' || name === 'ol' || name === 'li' || name === 'blockquote' || name === 'pre' || name === 'p') {
            const doDel = () => {
                $removeEl.remove();
                $(".collidingParagraph:empty").remove();
                MotionMergeChangeActions.removeEmptyParagraphs();
                if (onFinished) onFinished();
            }
            if (delayed) {
                $removeEl.css("overflow", "hidden").height($removeEl.height());
                $removeEl.animate({"height": "0"}, 250, () => doDel);
            } else {
                doDel();
            }
        } else {
            $removeEl.remove();
            if (onFinished) onFinished();
        }
    }
}
