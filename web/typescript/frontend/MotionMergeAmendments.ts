import { AntragsgruenEditor } from "../shared/AntragsgruenEditor";
import editor = CKEDITOR.editor;
import ClickEvent = JQuery.ClickEvent;
import { VueConstructor } from 'vue';

declare var Vue: VueConstructor;

const STATUS_ACCEPTED = 4;
const STATUS_MODIFIED_ACCEPTED = 6;
const STATUS_PROCESSED = 17;
const STATUS_ADOPTED = 8;
const STATUS_COMPLETED = 9;

enum AMENDMENT_VERSION {
    ORIGINAL = 'orig',
    PROPOSED_PROCEDURE = 'prop',
}

interface VotingData {
    votesYes: number;
    votesNo: number;
    votesAbstention: number;
    votesInvalid: number;
    comment: string;
}

class AmendmentStatuses {
    private static statuses: { [amendmentId: number]: number };
    private static versions: { [amendmentId: number]: AMENDMENT_VERSION };
    private static votingData: { [amendmentId: number]: VotingData };
    private static statusListeners: { [amendmentId: number]: MotionMergeAmendmentsParagraph[] } = {};

    public static init(
        statuses: { [amendmentId: number]: number },
        versions: { [amendmentId: number]: AMENDMENT_VERSION },
        votingData: { [amendmentId: number]: VotingData }
    ) {
        AmendmentStatuses.statuses = statuses;
        AmendmentStatuses.versions = versions;
        AmendmentStatuses.votingData = votingData;

        Object.keys(statuses).forEach(amendmentId => {
            AmendmentStatuses.statusListeners[amendmentId] = [];
        });
    }

    public static getAmendmentStatus(amendmentId: number): number {
        return AmendmentStatuses.statuses[amendmentId];
    }

    public static getAmendmentVersion(amendmentId: number): AMENDMENT_VERSION {
        return AmendmentStatuses.versions[amendmentId];
    }

    public static getAmendmentVotingData(amendmentId: number): VotingData {
        return AmendmentStatuses.votingData[amendmentId];
    }

    public static registerParagraph(amendmentId: number, paragraph: MotionMergeAmendmentsParagraph) {
        AmendmentStatuses.statusListeners[amendmentId].push(paragraph);
    }

    public static setStatus(amendmentId: number, status: number) {
        AmendmentStatuses.statuses[amendmentId] = status;
        AmendmentStatuses.statusListeners[amendmentId].forEach(paragraph => {
            paragraph.onAmendmentStatusChanged(amendmentId, status);
        });
    }

    public static setVersion(amendmentId: number, version: AMENDMENT_VERSION) {
        AmendmentStatuses.versions[amendmentId] = version;
        AmendmentStatuses.statusListeners[amendmentId].forEach(paragraph => {
            paragraph.onAmendmentVersionChanged();
        });
    }

    public static setVotesYes(amendmentId: number, votes: number) {
        AmendmentStatuses.votingData[amendmentId].votesYes = votes;
    }

    public static setVotesNo(amendmentId: number, votes: number) {
        AmendmentStatuses.votingData[amendmentId].votesNo = votes;
    }

    public static setVotesAbstention(amendmentId: number, votes: number) {
        AmendmentStatuses.votingData[amendmentId].votesAbstention = votes;
    }

    public static setVotesInvalid(amendmentId: number, votes: number) {
        AmendmentStatuses.votingData[amendmentId].votesInvalid = votes;
    }

    public static setVotesComment(amendmentId: number, comment: string) {
        AmendmentStatuses.votingData[amendmentId].comment = comment;
    }

    public static getAllStatuses(): { [amendmentId: number]: number } {
        return AmendmentStatuses.statuses;
    }

    public static getAllVersions(): { [amendmentId: number]: AMENDMENT_VERSION } {
        return AmendmentStatuses.versions;
    }

    public static getAllVotingData(): { [amendmentId: number]: VotingData } {
        return AmendmentStatuses.votingData;
    }
}

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
            MotionMergeChangeActions.deleteAccept(node, onFinished);
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

    public static deleteAccept(node: Element, onFinished: () => void = null) {
        let name = node.nodeName.toLowerCase(),
            $removeEl: JQuery;
        if (name == 'li') {
            $removeEl = $(node).parent() as JQuery;
        } else {
            $removeEl = $(node) as JQuery;
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
}


class MotionMergeChangeTooltip {
    constructor(private $element: JQuery, mouseX: number, mouseY: number, private parent: MotionMergeAmendmentsTextarea) {
        let positionX: number = null,
            positionY: number = null;
        $element.popover({
            'container': 'body',
            'animation': false,
            'trigger': 'manual',
            'placement': function (popover) {
                let $popover = $(<any>popover);
                $popover.data("element", $element);
                window.setTimeout(() => {
                    let width = $popover.width(),
                        elTop = $element.offset().top,
                        elHeight = $element.height();
                    if (positionX === null && width > 0) {
                        positionX = (mouseX - width / 2);
                        positionY = mouseY + 10;
                        if (positionY < (elTop + 19)) {
                            positionY = elTop + 19;
                        }
                        if (positionY > elTop + elHeight) {
                            positionY = elTop + elHeight;
                        }
                    }
                    $popover.css("left", positionX + "px");
                    $popover.css("top", positionY + "px");
                }, 1);
                return "bottom";
            },
            'html': true,
            'content': this.getContent.bind(this)
        });

        $element.popover('show');
        let $popover: JQuery = $element.find("> .popover");
        $popover.on("mousemove", (ev) => {
            ev.stopPropagation();
        });
        window.setTimeout(this.removePopupIfInactive.bind(this), 1000);
    }

    private getContent() {
        let $myEl: JQuery = this.$element,
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
            $el.find("button.accept").text(__t("merge", "change_accept")).on("click", this.accept.bind(this));
            $el.find("button.reject").text(__t("merge", "change_reject")).on("click", this.reject.bind(this));
        } else if ($myEl.hasClass("ice-del")) {
            $el.find("button.accept").text(__t("merge", "change_accept")).on("click", this.accept.bind(this));
            $el.find("button.reject").text(__t("merge", "change_reject")).on("click", this.reject.bind(this));
        } else if ($myEl[0].nodeName.toLowerCase() == 'li') {
            let $list = $myEl.parent();
            if ($list.hasClass("ice-ins")) {
                $el.find("button.accept").text(__t("merge", "change_accept")).on("click", this.accept.bind(this));
                $el.find("button.reject").text(__t("merge", "change_reject")).on("click", this.reject.bind(this));
            } else if ($list.hasClass("ice-del")) {
                $el.find("button.accept").text(__t("merge", "change_accept")).on("click", this.accept.bind(this));
                $el.find("button.reject").text(__t("merge", "change_reject")).on("click", this.reject.bind(this));
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
        this.destroy();
    }

    private affectedChangesets() {
        let cid = this.$element.data("cid");
        if (cid == undefined) {
            cid = this.$element.parent().data("cid");
        }
        return this.$element.parents(".texteditor").find("[data-cid=" + cid + "]");
    }

    private performActionWithUI(action) {
        let scrollX = window.scrollX,
            scrollY = window.scrollY;

        this.parent.saveEditorSnapshot();
        this.destroy();
        action.call(this);
        this.parent.focusTextarea();

        window.scrollTo(scrollX, scrollY);
    }

    private accept() {
        this.performActionWithUI(() => {
            this.affectedChangesets().each((i, el) => {
                MotionMergeChangeActions.accept(el, () => {
                    this.parent.onChanged();
                });
            });
        });
    }

    private reject() {
        this.performActionWithUI(() => {
            this.affectedChangesets().each((i, el) => {
                MotionMergeChangeActions.reject(el, () => {
                    this.parent.onChanged();
                });
            });
        });
    }

    public destroy() {
        this.$element.popover("hide").popover("destroy");

        let cid = this.$element.data("cid");
        if (cid == undefined) {
            cid = this.$element.parent().data("cid");
        }

        let focusAtSameCid = false;
        this.$element.parents(".texteditor").first().find("[data-cid=" + cid + "]").each((i, el) => {
            if ($(el).is(":hover")) {
                focusAtSameCid = true;
            }
        });
        if (!focusAtSameCid) {
            this.$element.parents(".texteditor").first().find("[data-cid=" + cid + "]").removeClass("hover");
        }

        try {
            // Remove stale objects that were not removed correctly previously
            $(".popover").each((i, stale) => {
                const $stale = $(stale);
                if (!$stale.data("element").is(":hover")) {
                    $stale.popover("hide").popover("destroy");
                    $stale.remove();
                    console.warn("Removed stale window: ", $stale);
                }
            });
        } catch (e) {
        }
    }
}

class MotionMergeAmendmentsTextarea {
    private texteditor: editor;
    private unchangedText: string = null;
    private hasChanged: boolean = false;
    private changedListeners: { (): void }[] = [];

    private prepareText(html: string) {
        let $text: JQuery = $('<div>' + html + '</div>');

        // Move the amendment-Data from OL's and UL's to their list items
        $text.find("ul.appendHint, ol.appendHint").each((i, el) => {
            let $this: JQuery = $(el),
                appendHint = $this.data("append-hint");
            $this.find("> li").addClass("appendHint").attr("data-append-hint", appendHint)
                .attr("data-link", $this.data("link"))
                .attr("data-username", $this.data("username"));
            $this.removeClass("appendHint").removeData("append-hint");
        });

        // Remove double markup
        $text.find(".moved .moved").removeClass('moved');
        $text.find(".moved").each(this.markupMovedParagraph.bind(this));

        let newText = $text.html();
        this.texteditor.setData(newText);
        this.unchangedText = this.normalizeHtml(this.texteditor.getData());
        this.texteditor.fire('saveSnapshot');
        this.onChanged();
    }

    public addChangedListener(cb: () => void) {
        this.changedListeners.push(cb);
    }

    private markupMovedParagraph(i, el) {
        let $node = $(el),
            paragraphNew = $node.data('moving-partner-paragraph'),
            msg: string;

        if ($node.hasClass('inserted')) {
            msg = __t('std', 'moved_paragraph_from');
        } else {
            msg = __t('std', 'moved_paragraph_to');
        }
        msg = msg.replace(/##PARA##/, (paragraphNew + 1));

        if ($node[0].nodeName === 'LI') {
            $node = $node.parent();
        }

        $node.attr("data-moving-msg", msg);
    }

    private initializeTooltips() {
        this.$holder.on("mouseover", ".appendHint", (ev) => {
            const $target = $(ev.currentTarget);
            if ($target.parents('.paragraphWrapper').first().find('.amendmentStatus.open').length > 0) {
                return;
            }
            if (MotionMergeAmendments.activePopup) {
                MotionMergeAmendments.activePopup.destroy();
            }
            MotionMergeAmendments.activePopup = new MotionMergeChangeTooltip($target, ev.pageX, ev.pageY, this);
        });
    }


    public acceptAll() {
        this.saveEditorSnapshot();
        this.$holder.find(".ice-ins").each((i, el) => {
            MotionMergeChangeActions.insertAccept(el);
        });
        this.$holder.find(".ice-del").each((i, el) => {
            MotionMergeChangeActions.deleteAccept(el);
        });
        this.onChanged();
        window.setTimeout(() => {
            // Wait for animation -> remove "all dropdown"
            this.onChanged();
            this.saveEditorSnapshot();
        }, 1000);
    }

    public rejectAll() {
        this.saveEditorSnapshot();
        this.$holder.find(".ice-ins").each((i, el) => {
            MotionMergeChangeActions.insertReject($(el));
        });
        this.$holder.find(".ice-del").each((i, el) => {
            MotionMergeChangeActions.deleteReject($(el));
        });
        this.onChanged();
        window.setTimeout(() => {
            // Wait for animation -> remove "all dropdown"
            this.onChanged();
            this.saveEditorSnapshot();
        }, 1000);
    }

    public saveEditorSnapshot() {
        this.texteditor.fire('saveSnapshot');
    }

    public focusTextarea() {
        //this.$holder.find(".texteditor").focus();
        // This lead to strange cursor behavior, e.g. when removing a colliding paragraph
    }

    public getContent(): string {
        return this.texteditor.getData();
    }

    public getUnchangedContent(): string {
        return this.unchangedText;
    }

    public setText(html: string) {
        this.prepareText(html);
        this.initializeTooltips();
    }

    private normalizeHtml(html: string) {
        const entities = {
            '&nbsp;': ' ',
            '&ndash;': '-',
            '&auml;': 'ä',
            '&ouml;': 'ö',
            '&uuml;': 'ü',
            '&Auml;': 'Ä',
            '&Ouml;': 'Ö',
            '&Uuml;': 'Ü',
            '&szlig;': 'ß',
            '&bdquo;': '„',
            '&ldquo;': '“',
            '&bull;': '•',
            '&sect;': '§',
            '&eacute;': 'é',
            '&rsquo;': '’',
            '&euro;': '€'
        };
        Object.keys(entities).forEach(ent => {
            html = html.replace(new RegExp(ent, 'g'), entities[ent]);
        });

        return html.replace(/\s+</g, '<').replace(/>\s+/g, '>').replace(/<[^>]*>/g, '');
    }

    public onChanged() {
        if (this.normalizeHtml(this.texteditor.getData()) === this.unchangedText) {
            this.$changedIndicator.addClass("unchanged");
            this.hasChanged = false;
        } else {
            this.$changedIndicator.removeClass("unchanged");
            this.hasChanged = true;
        }
        if (this.$holder.find(".ice-ins").length > 0 || this.$holder.find(".ice-del").length > 0) {
            this.$mergeActionHolder.removeClass("hidden");
        } else {
            this.$mergeActionHolder.addClass("hidden");
        }
        this.changedListeners.forEach(cb => cb());
    }

    public hasChanges(): boolean {
        return this.hasChanged;
    }

    constructor(private $holder: JQuery, private $changedIndicator: JQuery, private $mergeActionHolder: JQuery) {
        let $textarea = $holder.find(".texteditor");
        let edit = new AntragsgruenEditor($textarea.attr("id"));
        this.texteditor = edit.getEditor();

        this.setText(this.texteditor.getData());

        if ($holder.data("unchanged")) {
            this.unchangedText = $holder.data("unchanged");
            this.onChanged();
        }

        this.texteditor.on('change', this.onChanged.bind(this));
    }
}

class MotionMergeAmendmentsParagraph {
    public sectionId: number;
    public paragraphId: number;
    public textarea: MotionMergeAmendmentsTextarea;
    public hasUnsavedChanges = false;
    public handledCollisions: number[] = [];

    constructor(private $holder: JQuery, draft: any) {
        this.sectionId = parseInt($holder.data('sectionId'));
        this.paragraphId = parseInt($holder.data('paragraphId'));

        const paragraphDraft = draft.paragraphs && draft.paragraphs[this.sectionId + "_" + this.paragraphId] ? draft.paragraphs[this.sectionId + "_" + this.paragraphId] : null;
        if (paragraphDraft.handledCollisions) {
            this.handledCollisions = paragraphDraft.handledCollisions;
        }

        const $textarea = $holder.find(".wysiwyg-textarea");
        const $changed = $holder.find(".changedIndicator");
        const $mergeActionHolder = $holder.find(".mergeActionHolder");
        this.textarea = new MotionMergeAmendmentsTextarea($textarea, $changed, $mergeActionHolder);

        this.initButtons();
        this.initSetCollisionsAsHandled();

        const amendmentData = $holder.find(".changeToolbar .statuses").data("amendments");
        new Vue({
            el: $holder.find(".changeToolbar .statuses")[0],
            template: `<div class="statuses"><paragraph-amendment-settings v-for="data in amendmentData"
                                                     v-bind:amendment="data.amendment"
                                                     v-bind:nameBase="data.nameBase"
                                                     v-bind:idAdd="data.idAdd"
                                                     v-bind:active="data.active"
            ></paragraph-amendment-settings></div>`,
            data: {
                amendmentData,
            }
        });

        $holder.find(".amendmentStatus").each((i: number, element) => {
            AmendmentStatuses.registerParagraph($(element).data("amendment-id"), this);
        });

        this.textarea.addChangedListener(() => this.hasUnsavedChanges = true);
    }


    private initSetCollisionsAsHandled() {
        this.$holder.on("click", "button.hideCollision", (ev: ClickEvent) => {
            const $collision = $(ev.currentTarget).parents(".collidingParagraph").first();
            const amendmentId = parseInt($collision.data("amendment-id"), 10);
            const $collisionHolder = $collision.parent();
            $collision.remove();
            if ($collisionHolder.children().length === 0) {
                this.$holder.removeClass("hasCollisions");
            }
            this.handledCollisions.push(amendmentId);
            this.hasUnsavedChanges = true;
        });
    }

    private initButtons() {
        this.$holder.find('.toggleAmendment').on("click", (ev) => {
            const $input = $(ev.currentTarget).find(".amendmentActive");
            const doToggle = () => {
                if (parseInt($input.val() as string, 10) === 1) {
                    $input.val("0");
                    $input.parents(".btn-group").find(".btn").addClass("btn-default").removeClass("toggleActive");
                } else {
                    $input.val("1");
                    $input.parents(".btn-group").find(".btn").removeClass("btn-default").addClass("toggleActive");
                }
                this.reloadText();
                this.hasUnsavedChanges = true;
            };

            if (this.textarea.hasChanges()) {
                bootbox.confirm(__t('merge', 'reloadParagraph'), (result) => {
                    if (result) {
                        doToggle();
                    }
                });
            } else {
                doToggle();
            }
        });

        const initTooltip = ($holder: JQuery) => {
            const amendmentId = parseInt($holder.data("amendment-id"));
            const currentStatus = AmendmentStatuses.getAmendmentStatus(amendmentId);
            const currentVersion = AmendmentStatuses.getAmendmentVersion(amendmentId);
            const votingData = AmendmentStatuses.getAmendmentVotingData(amendmentId);

            console.log(currentVersion);

            $holder.find(".dropdown-menu .selected").removeClass("selected");
            $holder.find(".dropdown-menu .status" + currentStatus).addClass("selected");
            $holder.find(".dropdown-menu .version" + currentVersion).addClass("selected");
            $holder.find(".votesYes").val(votingData.votesYes);
            $holder.find(".votesNo").val(votingData.votesNo);
            $holder.find(".votesAbstention").val(votingData.votesAbstention);
            $holder.find(".votesInvalid").val(votingData.votesInvalid);
            $holder.find(".votesComment").val(votingData.comment);
        };

        this.$holder.find('.btn-group.amendmentStatus').on('show.bs.dropdown', ev => {
            initTooltip($(ev.currentTarget) as JQuery)
        });

        this.$holder.find(".btn-group .setStatus").on("click", ev => {
            ev.preventDefault();
            const $holder = $(ev.currentTarget).parents(".btn-group");
            const amendmentId = parseInt($holder.data("amendment-id"));
            AmendmentStatuses.setStatus(amendmentId, parseInt($(ev.currentTarget).data("status")));
            initTooltip($holder);
            this.hasUnsavedChanges = true;
        });

        this.$holder.find(".btn-group .setVersion").on("click", ev => {
            ev.preventDefault();
            const $holder = $(ev.currentTarget).parents(".btn-group");
            const amendmentId = parseInt($holder.data("amendment-id"));
            AmendmentStatuses.setVersion(amendmentId, $(ev.currentTarget).data("version"));
            initTooltip($holder);
            this.hasUnsavedChanges = true;
        });

        this.$holder.find(".btn-group .votesYes").on("keyup change", ev => {
            const $holder = $(ev.currentTarget).parents(".btn-group");
            const amendmentId = parseInt($holder.data("amendment-id"));
            AmendmentStatuses.setVotesYes(amendmentId, parseInt($(ev.currentTarget).val() as string, 10));
            this.hasUnsavedChanges = true;
        });

        this.$holder.find(".btn-group .votesNo").on("keyup change", ev => {
            const $holder = $(ev.currentTarget).parents(".btn-group");
            const amendmentId = parseInt($holder.data("amendment-id"));
            AmendmentStatuses.setVotesNo(amendmentId, parseInt($(ev.currentTarget).val() as string, 10));
            this.hasUnsavedChanges = true;
        });

        this.$holder.find(".btn-group .votesAbstention").on("keyup change", ev => {
            const $holder = $(ev.currentTarget).parents(".btn-group");
            const amendmentId = parseInt($holder.data("amendment-id"));
            AmendmentStatuses.setVotesAbstention(amendmentId, parseInt($(ev.currentTarget).val() as string, 10));
            this.hasUnsavedChanges = true;
        });

        this.$holder.find(".btn-group .votesInvalid").on("keyup change", ev => {
            const $holder = $(ev.currentTarget).parents(".btn-group");
            const amendmentId = parseInt($holder.data("amendment-id"));
            AmendmentStatuses.setVotesInvalid(amendmentId, parseInt($(ev.currentTarget).val() as string, 10));
            this.hasUnsavedChanges = true;
        });

        this.$holder.find(".btn-group .votesComment").on("keyup change", ev => {
            const $holder = $(ev.currentTarget).parents(".btn-group");
            const amendmentId = parseInt($holder.data("amendment-id"));
            AmendmentStatuses.setVotesComment(amendmentId, $(ev.currentTarget).val() as string);
            this.hasUnsavedChanges = true;
        });

        this.$holder.find(".mergeActionHolder .acceptAll").on("click", ev => {
            ev.preventDefault();
            this.textarea.acceptAll();
            this.hasUnsavedChanges = true;
        });

        this.$holder.find(".mergeActionHolder .rejectAll").on("click", ev => {
            ev.preventDefault();
            this.textarea.rejectAll();
            this.hasUnsavedChanges = true;
        });
    }

    public onAmendmentVersionChanged() {
        if (this.textarea.hasChanges()) {
            console.log("Skipping, as there are changes");
            return;
        }
        this.reloadText();
    }

    public onAmendmentStatusChanged(amendmentId: number, status: number) {
        if (this.textarea.hasChanges()) {
            console.log("Skipping, as there are changes");
            return;
        }
        const $holder = this.$holder.find(".amendmentStatus[data-amendment-id=" + amendmentId + "]");
        const $btn = $holder.find(".btn");
        const $input = $holder.find("input.amendmentActive");
        if ([
            STATUS_ACCEPTED,
            STATUS_MODIFIED_ACCEPTED,
            STATUS_PROCESSED,
            STATUS_ADOPTED,
            STATUS_COMPLETED
        ].indexOf(status) !== -1) {
            $input.val("1");
            $btn.removeClass("btn-default").addClass("toggleActive");
        } else {
            $input.val("0");
            $btn.addClass("btn-default").removeClass("toggleActive");
        }
        this.reloadText();
    }

    private reloadText() {
        const amendments = [];
        this.$holder.find(".amendmentActive[value='1']").each((i, el) => {
            const amendmentId = parseInt($(el).data('amendment-id'));
            amendments.push({
                id: amendmentId,
                version: AmendmentStatuses.getAmendmentVersion(amendmentId),
            });
        });
        const url = this.$holder.data("reload-url").replace('DUMMY', JSON.stringify(amendments));
        $.get(url, (data) => {
            this.textarea.setText(data.text);

            let collisions = '';
            data.collisions.forEach(str => {
                collisions += str;
            });

            this.$holder.find(".collisionsHolder").html(collisions);
            if (data.collisions.length > 0) {
                this.$holder.addClass("hasCollisions");
            } else {
                this.$holder.removeClass("hasCollisions");
            }
            this.handledCollisions = [];
            this.hasUnsavedChanges = true;
        });
    }

    public getDraftData() {
        const amendmentToggles = [];
        this.$holder.find(".amendmentStatus").each((id, el) => {
            const $el = $(el);
            if ($el.find(".toggleActive").length > 0) {
                amendmentToggles.push($el.data("amendment-id"));
            }
        });
        return {
            amendmentToggles,
            text: this.textarea.getContent(),
            unchanged: this.textarea.getUnchangedContent(),
            handledCollisions: this.handledCollisions,
        };
    }

    public onDraftChanged() {
        this.hasUnsavedChanges = false;
    }
}

/**
 * Singleton object
 */
export class MotionMergeAmendments {
    public static activePopup: MotionMergeChangeTooltip = null;
    public static currMouseX: number = null;
    public static $form;

    public $draftSavingPanel: JQuery;
    private paragraphs: MotionMergeAmendmentsParagraph[] = [];
    private hasUnsavedChanges = false;

    constructor($form: JQuery) {
        MotionMergeAmendments.$form = $form;

        const draft = JSON.parse(document.getElementById('mergeDraft').getAttribute('value'));
        AmendmentStatuses.init(draft.amendmentStatuses, draft.amendmentVersions, draft.amendmentVotingData);

        $(".paragraphWrapper").each((i, el) => {
            const $para = $(el);
            $para.find(".wysiwyg-textarea").on("mousemove", (ev) => {
                MotionMergeAmendments.currMouseX = ev.offsetX;
            });

            this.paragraphs.push(new MotionMergeAmendmentsParagraph($para, draft));
        });

        MotionMergeAmendments.$form.on("submit", () => {
            this.hasUnsavedChanges = true; // Enforce that the INPUT field is set
            this.saveDraft(true);
            $(window).off("beforeunload", MotionMergeAmendments.onLeavePage);
        });
        $(window).on("beforeunload", MotionMergeAmendments.onLeavePage);

        this.initDraftSaving();
        this.initRemovingSectionTexts();
    }

    public static onLeavePage(): string {
        return __t("std", "leave_changed_page");
    }

    private setDraftDate(date: Date) {
        this.$draftSavingPanel.find(".lastSaved .none").hide();

        let options = {
                year: 'numeric', month: 'numeric', day: 'numeric',
                hour: 'numeric', minute: 'numeric',
                hour12: false
            },
            lang: string = $("html").attr("lang"),
            formatted = new Intl.DateTimeFormat(lang, options).format(date);

        this.$draftSavingPanel.find(".lastSaved .value").text(formatted);
    }

    private initRemovingSectionTexts() {
        MotionMergeAmendments.$form.find(".removeSection input[type=checkbox]").on("change", ev => {
            const $checkbox = $(ev.currentTarget);
            const $section = $checkbox.parents(".section").first();
            if ($checkbox.prop("checked")) {
                $section.find(".sectionHolder").addClass("hidden");
            } else {
                $section.find(".sectionHolder").removeClass("hidden");
            }
        }).trigger("change");
    }

    private saveDraft(onlyInput = false) {
        if (this.paragraphs.filter(par => par.hasUnsavedChanges).length === 0 && !this.hasUnsavedChanges) {
            console.log("Has no unsaved changes");
            return;
        }

        const data = {
            "amendmentStatuses": AmendmentStatuses.getAllStatuses(),
            "amendmentVersions": AmendmentStatuses.getAllVersions(),
            "amendmentVotingData": AmendmentStatuses.getAllVotingData(),
            "paragraphs": {},
            "sections": {},
            "removedSections": [],
        };
        $(".sectionType0").each((i, el) => {
            const $section = $(el),
                sectionId = $section.data("section-id");
            data.sections[sectionId] = $section.find(".form-control").val();
        });
        MotionMergeAmendments.$form.find(".removeSection input[type=checkbox]:checked").each((i, el) => {
            data.removedSections.push(parseInt($(el).val() as string));
        });

        this.paragraphs.forEach(para => {
            data.paragraphs[para.sectionId + '_' + para.paragraphId] = para.getDraftData();
        });
        let isPublic: boolean = this.$draftSavingPanel.find('input[name=public]').prop('checked');

        const dataStr = JSON.stringify(data);
        document.getElementById('mergeDraft').setAttribute('value', dataStr);

        if (!onlyInput) {
            $.ajax({
                type: "POST",
                url: MotionMergeAmendments.$form.data('draftSaving'),
                data: {
                    'public': (isPublic ? 1 : 0),
                    data: dataStr,
                    '_csrf': MotionMergeAmendments.$form.find('> input[name=_csrf]').val()
                },
                success: (ret) => {
                    if (ret['success']) {
                        this.$draftSavingPanel.find('.savingError').addClass('hidden');
                        this.setDraftDate(new Date(ret['date']));
                        if (isPublic) {
                            MotionMergeAmendments.$form.find('.publicLink').removeClass('hidden');
                        } else {
                            MotionMergeAmendments.$form.find('.publicLink').addClass('hidden');
                        }
                    } else {
                        this.$draftSavingPanel.find('.savingError').removeClass('hidden');
                        this.$draftSavingPanel.find('.savingError .errorNetwork').addClass('hidden');
                        this.$draftSavingPanel.find('.savingError .errorHolder').text(ret['error']).removeClass('hidden');
                    }

                    this.paragraphs.forEach(par => par.onDraftChanged());
                    this.hasUnsavedChanges = false;
                },
                error: () => {
                    this.$draftSavingPanel.find('.savingError').removeClass('hidden');
                    this.$draftSavingPanel.find('.savingError .errorNetwork').removeClass('hidden');
                    this.$draftSavingPanel.find('.savingError .errorHolder').text('').addClass('hidden');
                }
            });
        }
    }

    private initAutosavingDraft() {
        let $toggle: JQuery = this.$draftSavingPanel.find('input[name=autosave]');

        window.setInterval(() => {
            if ($toggle.prop('checked')) {
                this.saveDraft(false);
            }
        }, 5000);

        if (localStorage) {
            let state = localStorage.getItem('merging-draft-auto-save');
            if (state !== null) {
                $toggle.prop('checked', (state == '1'));
            }
        }
        $toggle.on("change", () => {
            let active: boolean = $toggle.prop('checked');
            if (localStorage) {
                localStorage.setItem('merging-draft-auto-save', (active ? '1' : '0'));
            }
        }).trigger('change');
    }

    private initDraftSaving() {
        this.$draftSavingPanel = MotionMergeAmendments.$form.find('#draftSavingPanel');
        this.$draftSavingPanel.find('.saveDraft').on('click', () => {
            this.hasUnsavedChanges = true;
            this.saveDraft(false);
        });
        this.$draftSavingPanel.find('input[name=public]').on('change', () => {
            this.hasUnsavedChanges = true;
            this.saveDraft(false)
        });
        this.initAutosavingDraft();

        if (this.$draftSavingPanel.data("resumed-date")) {
            let date = new Date(this.$draftSavingPanel.data("resumed-date"));
            this.setDraftDate(date);
        }

        $(".sectionType0").on("change", () => this.hasUnsavedChanges = true);

        $("#yii-debug-toolbar").remove();
    }
}
