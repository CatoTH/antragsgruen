import { AntragsgruenEditor } from "../shared/AntragsgruenEditor";
import editor = CKEDITOR.editor;
import ClickEvent = JQuery.ClickEvent;

declare let Vue: any;

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

    public static registerNewAmendment(amendmentId: number, status: number, version: AMENDMENT_VERSION, votingData: VotingData) {
        AmendmentStatuses.statuses[amendmentId] = status;
        AmendmentStatuses.versions[amendmentId] = version;
        AmendmentStatuses.votingData[amendmentId] = votingData;
        AmendmentStatuses.statusListeners[amendmentId] = [];

        console.log("registered new amendment status", AmendmentStatuses.statuses, AmendmentStatuses.versions, AmendmentStatuses.votingData);
    }

    public static deleteAmendment(amendmentId: number) {
        delete(AmendmentStatuses.statuses[amendmentId]);
        delete(AmendmentStatuses.versions[amendmentId]);
        delete(AmendmentStatuses.votingData[amendmentId]);
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
            paragraph.onAmendmentVersionChanged(amendmentId, version);
        });
    }

    public static setVotesData(amendmentId: number, voteData: VotingData) {
        AmendmentStatuses.votingData[amendmentId] = voteData;
        AmendmentStatuses.statusListeners[amendmentId].forEach(paragraph => {
            paragraph.onAmendmentVotingChanged(amendmentId, voteData);
        });
    }

    public static getAmendmentIds(): number[] {
        return Object.keys(AmendmentStatuses.statuses).map(key => parseInt(key, 10));
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
            cid = $myEl.data("cid"),
            isAppendedCollision = ($myEl.data("appended-collision") === 1 || $myEl.parent().data("appended-collision") === 1),
            isModU = $myEl.data("is-modu") === 1;
        if (cid == undefined) {
            cid = $myEl.parent().data("cid");
        }
        $myEl.parents(".texteditor").first().find("[data-cid=" + cid + "]").addClass("hover");

        html = '';
        if (isAppendedCollision) {
            html += '<div class="mergingPopoverCollisionHint">⚠️ ' + __t("merge", "mergedCollisionHint") + '</div>';
        }
        html += '<div class="mergingPopoverButtons">';
        html += '<button type="button" class="accept btn btn-sm btn-default"></button>';
        html += '<button type="button" class="reject btn btn-sm btn-default"></button>';
        html += '<a href="#" class="btn btn-small btn-default opener" target="_blank"><span class="glyphicon glyphicon-new-window"></span></a>';
        html += '<div class="initiator" style="font-size: 0.8em;"></div>';
        html += '</div>';
        let $el: JQuery = $(html);
        $el.find(".opener").attr("href", $myEl.data("link")).attr("title", __t("merge", "title_open_in_blank"));
        if (isModU) {
            $el.find(".initiator").text(__t("merge", "modU"));
        } else {
            $el.find(".initiator").text(__t("merge", "initiated_by") + ": " + $myEl.data("username"));
        }
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

    private markupMovedParagraph(_, el) {
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

        return html.replace(/\s+</g, '<').replace(/>\s+/g, '>')
            .replace(/<[^>]*ice-ins[^>]*>/g, 'ice-ins') // make sure accepted insertions are still recognized as change
            .replace(/<ins[^>]*>/g, 'ice-ins')
            .replace(/<[^>]*>/g, '');
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
    public statusWidget: any;
    public statusWidgetComponent: any;

    constructor(private $holder: JQuery, draft: any, amendmentStaticData: any) {
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
        this.initStatusWidget(amendmentStaticData);

        $holder.find(".amendmentStatus").each((i: number, element) => {
            AmendmentStatuses.registerParagraph($(element).data("amendment-id"), this);
        });

        this.textarea.addChangedListener(() => this.hasUnsavedChanges = true);
    }

    private initStatusWidget(amendmentStaticData: any) {
        const amendmentParagraphData = this.$holder.find(".changeToolbar .statuses").data("amendments");
        for (let i = 0; i < amendmentParagraphData.length; i++) {
            const amendmentId = amendmentParagraphData[i].amendmentId;
            amendmentParagraphData[i]['amendment'] = amendmentStaticData.find(amend => amend.id === amendmentId);
            amendmentParagraphData[i]['status'] = AmendmentStatuses.getAmendmentStatus(amendmentId);
            amendmentParagraphData[i]['version'] = AmendmentStatuses.getAmendmentVersion(amendmentId);
            amendmentParagraphData[i]['votingData'] = JSON.parse(JSON.stringify(AmendmentStatuses.getAmendmentVotingData(amendmentId)));
        }

        const para = this;

        const doAfterAskIfChanged = (cb) => {
            if (para.textarea.hasChanges()) {
                bootbox.confirm(__t('merge', 'reloadParagraph'), (result) => {
                    if (result) {
                        cb();
                    }
                });
            } else {
                cb();
            }
        };

        para.statusWidget = Vue.createApp({
            template: `
                <div class="statuses">
                    <paragraph-amendment-settings v-for="data in amendmentParagraphData"
                                                  v-bind:amendment="data.amendment"
                                                  v-bind:nameBase="data.nameBase"
                                                  v-bind:idAdd="data.idAdd"
                                                  v-bind:active="data.active"
                                                  v-bind:status="data.status"
                                                  v-bind:version="data.version"
                                                  v-bind:votingData="data.votingData"
                                                  v-on:update="update($event)"
                    ></paragraph-amendment-settings>
                </div>`,
            data() { return {
                amendmentParagraphData,
            } },
            methods: {
                getAllAmendmentData() {
                    return this.amendmentParagraphData;
                },
                getAmendmentData(amendmentId) {
                    for (let i = 0; i < this.amendmentParagraphData.length; i++) {
                        if (this.amendmentParagraphData[i].amendmentId == amendmentId) {
                            return this.amendmentParagraphData[i];
                        }
                    }
                    return null;
                },
                setAmendmentActive(amendment, active) {
                    amendment.active = active;
                    para.reloadText();
                },
                update(eventData) {
                    // Events coming from the widget directly
                    const op = eventData[0];
                    const amendmentId = eventData[1],
                        amendment = this.getAmendmentData(amendmentId);
                    if (!amendment) {
                        return;
                    }
                    switch (op) {
                        case 'set-active':
                            doAfterAskIfChanged(() => this.setAmendmentActive(amendment, eventData[2]));
                            break;
                        case 'set-status':
                            AmendmentStatuses.setStatus(amendmentId, parseInt(eventData[2]));
                            break;
                        case 'set-votes':
                            AmendmentStatuses.setVotesData(amendmentId, eventData[2]);
                            break;
                        case 'set-version':
                            doAfterAskIfChanged(() => {
                                // Do this no matter what - not only if it's unchanged
                                AmendmentStatuses.setVersion(amendmentId, eventData[2]);
                                para.reloadText();
                            });
                            break;
                    }
                    para.hasUnsavedChanges = true;
                },
                onStatusUpdated(amendmentId, newStatus) {
                    const amendment = this.getAmendmentData(amendmentId);
                    if (amendment) {
                        amendment.status = newStatus;
                        if (!para.textarea.hasChanges()) {
                            amendment.active = ([STATUS_ACCEPTED, STATUS_MODIFIED_ACCEPTED, STATUS_PROCESSED, STATUS_ADOPTED, STATUS_COMPLETED].indexOf(newStatus) !== -1);
                            para.reloadText();
                        }
                    }
                },
                onVotingUpdated(amendmentId, votingData) {
                    const amendment = this.getAmendmentData(amendmentId);
                    if (amendment) {
                        amendment.votingData = votingData;
                    }
                },
                onVersionUpdated(amendmentId, version) {
                    const amendment = this.getAmendmentData(amendmentId);
                    if (amendment) {
                        amendment.version = version;
                        if (!para.textarea.hasChanges()) {
                            para.reloadText();
                        }
                    }
                },
                onAmendmentAdded(amendment, nameBase, idAdd, active, status, verstion, votingData) {
                    this.amendmentParagraphData.push({
                        amendmentId: amendment.id,
                        amendment, nameBase, idAdd, active, status, verstion, votingData
                    });
                },
                onAmendmentDeleted(amendmentId) {
                    this.amendmentParagraphData = this.amendmentParagraphData.filter(amend => amend.amendmentId != amendmentId);
                }
            }
        });

        para.statusWidget.config.compilerOptions.whitespace = 'condense';
        window['__initVueComponents'](para.statusWidget, 'merging');
        para.statusWidgetComponent = para.statusWidget.mount(this.$holder.find(".changeToolbar .statuses")[0]);
    }

    public onAmendmentVersionChanged(amendmentId: number, version: string) {
        this.statusWidgetComponent.onVersionUpdated(amendmentId, version);
    }

    public onAmendmentVotingChanged(amendmentId: number, votingData: VotingData) {
        this.statusWidgetComponent.onVotingUpdated(amendmentId, votingData);
    }

    public onAmendmentStatusChanged(amendmentId: number, status: number) {
        this.statusWidgetComponent.onStatusUpdated(amendmentId, status);
    }

    public onAmendmentAdded(amendment, nameBase, idAdd, active, status, verstion, votingData) {
        this.statusWidgetComponent.onAmendmentAdded(amendment, nameBase, idAdd, active, status, verstion, votingData);
    }

    public onAmendmentDeleted(amendmentId) {
        this.statusWidgetComponent.onAmendmentDeleted(amendmentId);
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

    private reloadText() {
        const amendments = this.statusWidgetComponent.getAllAmendmentData()
            .filter(amendmentData => amendmentData.active)
            .map(amendmentData => {
                return {
                    id: amendmentData.amendmentId,
                    version: AmendmentStatuses.getAmendmentVersion(amendmentData.amendmentId),
                }
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
        const amendmentToggles = this.statusWidgetComponent.getAllAmendmentData()
            .filter(amendmentData => amendmentData.active)
            .map(amendmentData => amendmentData.amendmentId);
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
    public $newAmendmentAlert: JQuery;
    private paragraphsByTypeAndNo: {[typeAndPara: string]: MotionMergeAmendmentsParagraph} = {};
    private hasUnsavedChanges = false;

    constructor($form: JQuery) {
        MotionMergeAmendments.$form = $form;

        const draft = JSON.parse(document.getElementById('mergeDraft').getAttribute('value'));
        AmendmentStatuses.init(draft.amendmentStatuses, draft.amendmentVersions, draft.amendmentVotingData);

        const amendmentStaticData = $form.data('amendment-static-data');

        $(".paragraphWrapper").each((i, el) => {
            const $para = $(el);
            const sectionId = $para.data("section-id");
            const paragraphId = $para.data("paragraph-id");
            $para.find(".wysiwyg-textarea").on("mousemove", (ev) => {
                MotionMergeAmendments.currMouseX = ev.offsetX;
            });

            this.paragraphsByTypeAndNo[sectionId + '_' + paragraphId] = new MotionMergeAmendmentsParagraph($para, draft, amendmentStaticData);
        });

        MotionMergeAmendments.$form.on("submit", () => {
            this.hasUnsavedChanges = true; // Enforce that the INPUT field is set
            this.saveDraft(true);
            $(window).off("beforeunload", MotionMergeAmendments.onLeavePage);
        });
        $(window).on("beforeunload", MotionMergeAmendments.onLeavePage);

        this.initDraftSaving();
        this.initNewAmendmentAlert();
        this.initCheckBackendStatus();
        this.initRemovingSectionTexts();
        this.initProtocol();
    }

    public static onLeavePage(): string {
        return __t("std", "leave_changed_page");
    }

    private setDraftDate(date: Date) {
        this.$draftSavingPanel.find(".lastSaved .none").hide();

        let options: Intl.DateTimeFormatOptions = {
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

    private initProtocol() {
        const $textarea = $("#protocol_text_wysiwyg");
        $textarea.attr("contenteditable", "true");
        const ckeditor = new AntragsgruenEditor($textarea.attr("id"));
        const editor = ckeditor.getEditor();

        $textarea.parents("form").on("submit", () => {
            $textarea.parent().find("textarea").val(editor.getData());
        });
    }

    private saveDraft(onlyInput = false) {
        if (Object.keys(this.paragraphsByTypeAndNo).map(id => this.paragraphsByTypeAndNo[id])
            .filter(par => par.hasUnsavedChanges).length === 0 && !this.hasUnsavedChanges) {
            return;
        }

        console.log("Has unsaved changes");

        const protocolPublic = $("input[name=protocol_public]:checked").val() as string;
        const data = {
            "amendmentStatuses": AmendmentStatuses.getAllStatuses(),
            "amendmentVersions": AmendmentStatuses.getAllVersions(),
            "amendmentVotingData": AmendmentStatuses.getAllVotingData(),
            "paragraphs": {},
            "sections": {},
            "removedSections": [],
            "protocol": CKEDITOR.instances['protocol_text_wysiwyg'].getData(),
            "protocolPublic": parseInt(protocolPublic) === 1,
        };
        $(".sectionType0").each((i, el) => {
            const $section = $(el),
                sectionId = $section.data("section-id");
            data.sections[sectionId] = $section.find(".form-control").val();
        });
        MotionMergeAmendments.$form.find(".removeSection input[type=checkbox]:checked").each((i, el) => {
            data.removedSections.push(parseInt($(el).val() as string));
        });

        Object.keys(this.paragraphsByTypeAndNo).forEach(paraId => {
            data.paragraphs[paraId] = this.paragraphsByTypeAndNo[paraId].getDraftData();
        });
        let isPublic: boolean = this.$draftSavingPanel.find('input[name=public]').prop('checked');

        const dataStr = JSON.stringify(data);
        document.getElementById('mergeDraft').setAttribute('value', dataStr);

        if (!onlyInput) {
            $.ajax({
                type: "POST",
                url: MotionMergeAmendments.$form.data('draftSavingUrl'),
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

                    Object.keys(this.paragraphsByTypeAndNo).forEach(parId => this.paragraphsByTypeAndNo[parId].onDraftChanged());
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

    private initNewAmendmentAlert() {
        this.$newAmendmentAlert = MotionMergeAmendments.$form.find('#newAmendmentAlert');
        this.$newAmendmentAlert.find('.closeLink').on('click', () => {
            this.$newAmendmentAlert.find('.buttons').children().remove();
            this.$newAmendmentAlert.removeClass('revealed');
            window.setTimeout(() => {
                this.$newAmendmentAlert.addClass('hidden');
            }, 1000);
        });
    }

    private alertAboutNewAmendment(amendmentId: number, title: string) {
        const $buttons = this.$newAmendmentAlert.find('.buttons');
        const $newButton = $('<button class="btn-link gotoAmendment" type="button"></button>').text(title);
        $newButton.on('click', () => {
            const $firstToggle = $(".amendmentStatus" + amendmentId).first();
            const $paragraph = $firstToggle.parents('.paragraphWrapper');
            $paragraph.scrollintoview({top_offset: -100});
        });
        $buttons.append($newButton);

        if ($buttons.children().length > 1) {
            this.$newAmendmentAlert.find('.message .one').addClass('hidden');
            this.$newAmendmentAlert.find('.message .many').removeClass('hidden');
        } else {
            this.$newAmendmentAlert.find('.message .one').removeClass('hidden');
            this.$newAmendmentAlert.find('.message .many').addClass('hidden');
        }

        if (this.$newAmendmentAlert.hasClass('hidden')) {
            this.$newAmendmentAlert.removeClass('hidden');
            window.setTimeout(() => {
                this.$newAmendmentAlert.addClass('revealed');
            }, 100);
        }
    }

    private initCheckBackendStatus() {
        window.setInterval(() => {
            let url = MotionMergeAmendments.$form.data('checkStatusUrl');
            const amendmentIds = AmendmentStatuses.getAmendmentIds();
            url = url.replace(/AMENDMENTS/, amendmentIds.join(','));
            $.get(url, data => {
                if (data['success']) {
                    this.onReceivedBackendStatus(data['new'], data['deleted']);
                } else {
                    console.warn(data);
                }
            });
        }, 3000);
    }

    private onReceivedBackendStatus(newAmendments: any[], deletedAmendments: any[]) {
        const newAmendmentStaticData = {},
            newAmendmentStatus = {};
        newAmendments['staticData'].forEach(amendmentData => {
            const status = newAmendments['status'][amendmentData['id']];
            newAmendmentStaticData[amendmentData['id']] = amendmentData;
            newAmendmentStatus[amendmentData['id']] = status;

            AmendmentStatuses.registerNewAmendment(amendmentData['id'], status['status'], status['version'], status['votingData']);

            this.alertAboutNewAmendment(amendmentData['id'], amendmentData['titlePrefix']);
        });

        Object.keys(newAmendments['paragraphs']).forEach(typeId => {
            Object.keys(newAmendments['paragraphs'][typeId]).forEach(paragraphNo => {
                const paraObj = this.paragraphsByTypeAndNo[typeId + '_' + paragraphNo];
                newAmendments['paragraphs'][typeId][paragraphNo].forEach(data => {
                    const paraAmendmentData = newAmendmentStaticData[data.amendmentId];
                    const status = newAmendmentStatus[data.amendmentId];
                    paraObj.onAmendmentAdded(paraAmendmentData, data['nameBase'], data['idAdd'], data['active'], status['status'], status['version'], status['votingData'])
                    AmendmentStatuses.registerParagraph(data.amendmentId, paraObj);
                });
            });
        });

        deletedAmendments.forEach(amendmentId => {
            console.log("Removing amendment", amendmentId);
            AmendmentStatuses.deleteAmendment(amendmentId);

            Object.keys(this.paragraphsByTypeAndNo).forEach(id => {
                this.paragraphsByTypeAndNo[id].onAmendmentDeleted(amendmentId);
            });
        });
    }
}
