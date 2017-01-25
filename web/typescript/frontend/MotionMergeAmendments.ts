import {AntragsgruenEditor} from "../shared/AntragsgruenEditor";
import editor = CKEDITOR.editor;
import {DraftSavingEngine} from "../shared/DraftSavingEngine";

class MotionMergeChangeActions {
    public static accept(node: Element) {
        if ($(node).hasClass("ice-ins")) {
            MotionMergeChangeActions.insertAccept(node);
        }
        if ($(node).hasClass("ice-del")) {
            MotionMergeChangeActions.deleteAccept(node);
        }
    }

    public static reject(node: Element) {
        if ($(node).hasClass("ice-ins")) {
            MotionMergeChangeActions.insertReject(node);
        }
        if ($(node).hasClass("ice-del")) {
            MotionMergeChangeActions.deleteReject(node);
        }
    }

    public static insertReject(node: Element) {
        let $removeEl: JQuery,
            name = node.nodeName.toLowerCase();
        if (name == 'li') {
            $removeEl = $(node).parent();
        } else {
            $removeEl = $(node);
        }
        if (name == 'ul' || name == 'ol' || name == 'li' || name == 'blockquote' || name == 'pre' || name == 'p') {
            $removeEl.css("overflow", "hidden").height($removeEl.height());
            $removeEl.animate({"height": "0"}, 250, function () {
                $removeEl.remove();
                $(".collidingParagraph:empty").remove();
            });
        } else {
            $removeEl.remove();
        }
    }

    public static insertAccept(node: Element) {
        let $this: JQuery = $(node);
        $this.removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint");
        if (node.nodeName.toLowerCase() == 'ul' || node.nodeName.toLowerCase() == 'ol') {
            $this.children().removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint");
        }
        if (node.nodeName.toLowerCase() == 'li') {
            $this.parent().removeClass("ice-cts").removeClass("ice-ins").removeClass("appendHint");
        }
        if (node.nodeName.toLowerCase() == 'ins') {
            $this.replaceWith($this.html());
        }
    }

    public static deleteReject(node: Element) {
        let $this: JQuery = $(node);
        $this.removeClass("ice-cts").removeClass("ice-del").removeClass("appendHint");
        if (node.nodeName.toLowerCase() == 'ul' || node.nodeName.toLowerCase() == 'ol') {
            $this.children().removeClass("ice-cts").removeClass("ice-del").removeClass("appendHint");
        }
        if (node.nodeName.toLowerCase() == 'li') {
            $this.parent().removeClass("ice-cts").removeClass("ice-del").removeClass("appendHint");
        }
        if (node.nodeName.toLowerCase() == 'del') {
            $this.replaceWith($this.html());
        }
    }

    public static deleteAccept(node: Element) {
        let name = node.nodeName.toLowerCase(),
            $removeEl: JQuery;
        if (name == 'li') {
            $removeEl = $(node).parent();
        } else {
            $removeEl = $(node);
        }

        if (name == 'ul' || name == 'ol' || name == 'li' || name == 'blockquote' || name == 'pre' || name == 'p') {
            $removeEl.css("overflow", "hidden").height($removeEl.height());
            $removeEl.animate({"height": "0"}, 250, function () {
                $removeEl.remove();
                $(".collidingParagraph:empty").remove();
            });
        } else {
            $removeEl.remove();
        }
    }
}


class MotionMergeChangeTooltip {
    constructor(private $element, currMouseX, private parent: MotionMergeAmendmentsTextarea) {
        $element.popover({
            'container': 'body',
            'animation': false,
            'trigger': 'manual',
            'placement': 'bottom',
            'html': true,
            'content': this.getContent.bind(this)
        });

        $element.popover('show');
        let $popover = $element.find("> .popover"),
            width = $popover.width();
        $popover.css("left", Math.floor(currMouseX - (width / 2) + 20) + "px");
        $popover.on("mousemove", (ev) => {
            ev.stopPropagation();
        });
        window.setTimeout(this.removePopupIfInactive.bind(this), 500);
    }

    private getContent() {
        let $myEl = this.$element,
            html,
            cid = $myEl.data("cid");
        if (cid == undefined) {
            cid = $myEl.parent().data("cid");
        }
        $myEl.parents(".texteditor").first().find("[data-cid=" + cid + "]").addClass("hover");

        html = '<div>';
        html += '<button type="button" class="accept btn btn-sm btn-default"></button>';
        html += '<button type="button" class="reject btn btn-sm btn-default"></button>';
        html += '<a href="#" class="btn btn-small btn-default opener" target="_blank"><span class="glyphicon glyphicon-new-window"></span></a>';
        html += '<div class="initiator" style="font-size: 0.8em;"></div>';
        html += '</div>';
        let $el: JQuery = $(html);
        $el.find(".opener").attr("href", $myEl.data("link")).attr("title", __t("merge", "title_open_in_blank"));
        $el.find(".initiator").text(__t("merge", "initiated_by") + ": " + $myEl.data("username"));
        if ($myEl.hasClass("ice-ins")) {
            $el.find("button.accept").text(__t("merge", "insert_accept")).click(this.accept.bind(this));
            $el.find("button.reject").text(__t("merge", "insert_reject")).click(this.reject.bind(this));
        } else if ($myEl.hasClass("ice-del")) {
            $el.find("button.accept").text(__t("merge", "delete_accept")).click(this.accept.bind(this));
            $el.find("button.reject").text(__t("merge", "delete_reject")).click(this.reject.bind(this));
        } else if ($myEl[0].nodeName.toLowerCase() == 'li') {
            let $list = $myEl.parent();
            if ($list.hasClass("ice-ins")) {
                $el.find("button.accept").text(__t("merge", "insert_accept")).click(this.accept.bind(this));
                $el.find("button.reject").text(__t("merge", "insert_reject")).click(this.reject.bind(this));
            } else if ($list.hasClass("ice-del")) {
                $el.find("button.accept").text(__t("merge", "delete_accept")).click(this.accept.bind(this));
                $el.find("button.reject").text(__t("merge", "delete_reject")).click(this.reject.bind(this));
            } else {
                console.log("unknown", $list);
            }
        } else {
            console.log("unknown", $myEl);
            alert("unknown");
        }
        return $el;
    }

    private removePopupIfInactive() {
        if (this.$element.is(":hover")) {
            return window.setTimeout(this.removePopupIfInactive.bind(this), 1000);
        }
        if ($("body").find(".popover:hover").length > 0) {
            return window.setTimeout(this.removePopupIfInactive.bind(this), 1000);
        }
        this.$element.popover("hide").popover("destroy");

        let cid = this.$element.data("cid");
        if (cid == undefined) {
            cid = this.$element.parent().data("cid");
        }
        this.$element.parents(".texteditor").first().find("[data-cid=" + cid + "]").removeClass("hover");
    }

    private affectedChangesets() {
        let cid = this.$element.data("cid");
        if (cid == undefined) {
            cid = this.$element.parent().data("cid");
        }
        return this.$element.parents(".texteditor").find("[data-cid=" + cid + "]");
    }

    private performActionWithUI(action) {
        this.parent.saveEditorSnapshot();
        this.destroy();
        action.call(this);
        $(".collidingParagraph:empty").remove();
        this.parent.focusTextarea();
    }

    private accept() {
        this.performActionWithUI(() => {
            this.affectedChangesets().each((i, el) => {
                MotionMergeChangeActions.accept(el);
            });
        });
    }

    private reject() {
        this.performActionWithUI(() => {
            this.affectedChangesets().each((i, el) => {
                MotionMergeChangeActions.reject(el);
            });
        });
    }

    public destroy() {
        this.$element.popover("hide").popover("destroy")
    }
}

class MotionMergeConflictTooltip {
    constructor(private $element, currMouseX, private parent: MotionMergeAmendmentsTextarea) {
        $element.popover({
            'container': 'body',
            'animation': false,
            'trigger': 'manual',
            'placement': 'bottom',
            'html': true,
            'title': __t("merge", "colliding_title"),
            'content': this.getContent.bind(this)
        });
        $element.popover('show');

        let $popover = $("body > .popover"),
            width = $popover.width();
        console.log($popover, $element, $element.offset().left, width, currMouseX);
        $popover.css("left", Math.floor($element.offset().left + currMouseX - (width / 2) + 20) + "px");
        $popover.on("mousemove", function (ev) {
            ev.stopPropagation();
        });
        window.setTimeout(this.removePopupIfInactive.bind(this), 500);
    }

    private removePopupIfInactive() {
        if (this.$element.is(":hover")) {
            return window.setTimeout(this.removePopupIfInactive.bind(this), 1000);
        }
        if ($("body").find(".popover:hover").length > 0) {
            return window.setTimeout(this.removePopupIfInactive.bind(this), 1000);
        }
        this.$element.popover("hide").popover("destroy");

        let cid = this.$element.data("cid");
        if (cid == undefined) {
            cid = this.$element.parent().data("cid");
        }
        this.$element.parents(".texteditor").first().find("[data-cid=" + cid + "]").removeClass("hover");
    }

    private performActionWithUI(action) {
        this.parent.saveEditorSnapshot();
        this.destroy();
        action.call(this);
        $(".collidingParagraph:empty").remove();
        this.parent.focusTextarea();
    }


    private getContent() {
        let $this = this.$element,
            html = '<div style="white-space: nowrap;"><button type="button" class="btn btn-small btn-default delTitle">' +
                '<span style="text-decoration: line-through">' + __t("merge", "title") + '</span></button>';
        html += '<button type="button" class="reject btn btn-small btn-default"><span class="glyphicon glyphicon-trash"></span></button>';
        html += '<a href="#" class="btn btn-small btn-default opener" target="_blank"><span class="glyphicon glyphicon-new-window"></span></a>';
        html += '<div class="initiator" style="font-size: 0.8em;"></div>';
        html += '</div>';
        let $el = $(html);
        $el.find(".delTitle").attr("title", __t("merge", "title_del_title"));
        $el.find(".reject").attr("title", __t("merge", "title_del_colliding"));
        $el.find("a.opener").attr("href", $this.find("a").attr("href")).attr("title", __t("merge", "title_open_in_blank"));
        $el.find(".initiator").text(__t("merge", "initiated_by") + ": " + $this.parents(".collidingParagraph").data("username"));
        $el.find(".reject").click(() => {
            this.performActionWithUI.call(this, () => {
                let $para = $this.parents(".collidingParagraph");
                $para.css({"overflow": "hidden"}).height($para.height());
                $para.animate({"height": "0"}, 250, function () {
                    $para.remove();
                });
            });
        });
        $el.find(".delTitle").click(() => {
            this.performActionWithUI.call(this, () => {
                $this.remove();
            });
        });
        return $el;
    }

    public destroy() {
        this.$element.popover("hide").popover("destroy")
    }
}

class MotionMergeAmendmentsTextarea {
    private texteditor: editor;

    private prepareText() {
        let $text = $('<div>' + this.texteditor.getData() + '</div>');

        // Move the amendment-Data from OL's and UL's to their list items
        $text.find("ul.appendHint, ol.appendHint").each((i, el) => {
            let $this = $(el),
                appendHint = $this.data("append-hint");
            $this.find("> li").addClass("appendHint").attr("data-append-hint", appendHint)
                .attr("data-link", $this.data("link"))
                .attr("data-username", $this.data("username"));
            $this.removeClass("appendHint").removeData("append-hint");
        });

        let newText = $text.html();
        this.texteditor.setData(newText);
    }

    private initializeTooltips() {
        this.$holder.on('mouseover', '.collidingParagraphHead', (ev) => {
            $(ev.target).parents(".collidingParagraph").addClass("hovered");

            if (MotionMergeAmendments.activePopup) {
                MotionMergeAmendments.activePopup.destroy();
            }
            console.log(ev);
            MotionMergeAmendments.activePopup = new MotionMergeConflictTooltip(
                $(ev.currentTarget), MotionMergeAmendments.currMouseX, this
            );
        }).on('mouseout', '.collidingParagraphHead', (ev) => {
            $(ev.target).parents(".collidingParagraph").removeClass("hovered");
        });

        this.$holder.on("mouseover", ".appendHint", (ev) => {
            if (MotionMergeAmendments.activePopup) {
                MotionMergeAmendments.activePopup.destroy();
            }
            MotionMergeAmendments.activePopup = new MotionMergeChangeTooltip(
                $(ev.target), MotionMergeAmendments.currMouseX, this
            );
        });
    }

    private acceptAll() {
        this.texteditor.fire('saveSnapshot');
        this.$holder.find(".collidingParagraph").each((i, el) => {
            let $this = $(el);
            $this.find(".collidingParagraphHead").remove();
            $this.replaceWith($this.children());
        });
        this.$holder.find(".ice-ins").each((i, el) => {
            MotionMergeChangeActions.insertAccept(el);
        });
        this.$holder.find(".ice-del").each((i, el) => {
            MotionMergeChangeActions.deleteAccept(el);
        });
    }

    private rejectAll() {
        this.texteditor.fire('saveSnapshot');
        this.$holder.find(".collidingParagraph").each((i, el) => {
            $(el).remove();
        });
        this.$holder.find(".ice-ins").each((i, el) => {
            MotionMergeChangeActions.insertReject(el);
        });
        this.$holder.find(".ice-del").each((i, el) => {
            MotionMergeChangeActions.deleteReject(el);
        });
    }

    public saveEditorSnapshot() {
        this.texteditor.fire('saveSnapshot');
    }

    public focusTextarea() {
        this.$holder.find(".texteditor").focus();
    }

    constructor(private $holder: JQuery, private rootObject: MotionMergeAmendments) {
        let $textarea = $holder.find(".texteditor");
        let edit = new AntragsgruenEditor($textarea.attr("id"));
        this.texteditor = edit.getEditor();
        this.rootObject.addSubmitListener(() => {
            $holder.find("textarea.raw").val(this.texteditor.getData());
            $holder.find("textarea.consolidated").val(this.texteditor.getData());
        });

        this.prepareText();
        this.initializeTooltips();

        this.$holder.find(".acceptAllChanges").click(this.acceptAll.bind(this));
        this.$holder.find(".rejectAllChanges").click(this.rejectAll.bind(this));
    }
}

class MotionMergeAmendments {
    public static activePopup: MotionMergeChangeTooltip|MotionMergeConflictTooltip = null;
    public static currMouseX: number = null;

    constructor(private $form: JQuery) {
        $(".wysiwyg-textarea").each((i, el) => {
            new MotionMergeAmendmentsTextarea($(el), this);
            $(el).on("mousemove", (ev) => {
                MotionMergeAmendments.currMouseX = ev.offsetX;
            });
        });

        let $draftHint = $("#draftHint"),
            origMotionId = $draftHint.data("orig-motion-id"),
            newMotionId = $draftHint.data("new-motion-id");
        new DraftSavingEngine($form, $draftHint, "motionmerge_" + origMotionId + "_" + newMotionId);
    }

    public addSubmitListener(cb) {
        this.$form.submit(cb);
    }
}

new MotionMergeAmendments($(".motionMergeForm"));
