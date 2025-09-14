export class MotionMergeChangeActions {
    public static removeEmptyParagraphs() {
        $('.texteditor').each((i, el) => {
            if (el.childNodes.length == 0) {
                $(el).remove();
            }
        });
    }

    public static accept(node: Element, onFinished: () => void = null) {
        let $node = $(node);
        if ($node.hasClass("ice-ins")) {
            MotionMergeChangeActions.insertAccept(node, onFinished);
        }
        if ($node.hasClass("ice-del")) {
            MotionMergeChangeActions.deleteAccept(node, true, onFinished);
        }
    }

    public static reject(node: Element, onFinished: () => void = null) {
        let $node = $(node) as JQuery;
        if ($node.hasClass("ice-ins")) {
            MotionMergeChangeActions.insertReject($node, onFinished);
        }
        if ($node.hasClass("ice-del")) {
            MotionMergeChangeActions.deleteReject($node, onFinished);
        }
    }

    public static insertReject($node: JQuery, onFinished: () => void = null) {
        let $removeEl: JQuery,
            name = $node[0].nodeName.toLowerCase();
        if (name == 'li') {
            $removeEl = $node.parent();
        } else {
            $removeEl = $node;
        }
        if (name == 'ul' || name == 'ol' || name == 'li' || name == 'blockquote' || name == 'pre' || name == 'p') {
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

    public static insertAccept(node: Element, onFinished: () => void = null) {
        let $this = $(node) as JQuery;
        $this.removeClass("ice-cts ice-ins appendHint moved");
        $this.removeAttr("data-moving-partner data-moving-partner-id data-moving-partner-paragraph data-moving-msg");
        if (node.nodeName.toLowerCase() == 'ul' || node.nodeName.toLowerCase() == 'ol') {
            $this.children().removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint");
        }
        if (node.nodeName.toLowerCase() == 'li') {
            $this.parent().removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint");
        }
        if (node.nodeName.toLowerCase() == 'ins') {
            $this.replaceWith($this.html());
        }
        if (onFinished) onFinished();
    }

    public static deleteReject($node: JQuery, onFinished: () => void = null) {
        $node.removeClass("ice-cts ice-del appendHint");
        $node.removeAttr("data-moving-partner data-moving-partner-id data-moving-partner-paragraph data-moving-msg");
        let nodeName = $node[0].nodeName.toLowerCase();
        if (nodeName == 'ul' || nodeName == 'ol') {
            $node.children().removeClass("ice-cts").removeClass("ice-del").removeClass("appendHint");
        }
        if (nodeName == 'li') {
            $node.parent().removeClass("ice-cts").removeClass("ice-del").removeClass("appendHint");
        }
        if (nodeName == 'del') {
            $node.replaceWith($node.html());
        }
        if (onFinished) onFinished();
    }

    public static deleteAccept(node: Element, delayed: boolean, onFinished: () => void = null) {
        let name = node.nodeName.toLowerCase(),
            $removeEl: JQuery;
        if (name == 'li') {
            $removeEl = $(node).parent() as JQuery;
        } else {
            $removeEl = $(node) as JQuery;
        }

        if (name == 'ul' || name == 'ol' || name == 'li' || name == 'blockquote' || name == 'pre' || name == 'p') {
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
