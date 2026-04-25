// @ts-check

import { AntragsgruenEditor } from "../shared/AntragsgruenEditor.js";
import { MotionMergeChangeActions } from '../shared/MotionMergeChangeActions.js';
import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
import translateDirective from "/js/vue/Translate.vue.js";
import ParagraphAmendmentSettings from "/js/vue/merging/ParagraphAmendmentSettings.js";

const STATUS_ACCEPTED = 4;
const STATUS_MODIFIED_ACCEPTED = 6;
const STATUS_PROCESSED = 17;
const STATUS_ADOPTED = 8;
const STATUS_COMPLETED = 9;

/**
 * @typedef {string} AMENDMENT_VERSION
 */

/**
 * @typedef {Object} VotingData
 * @property {number} votesYes
 * @property {number} votesNo
 * @property {number} votesAbstention
 * @property {number} votesInvalid
 * @property {string} comment
 */

// ============================================================
// AmendmentStatuses
// ============================================================

class AmendmentStatuses {
    /** @type {Object<number, number>} */
    static statuses;

    /** @type {Object<number, AMENDMENT_VERSION>} */
    static versions;

    /** @type {Object<number, VotingData>} */
    static votingData;

    /** @type {Object<number, MotionMergeAmendmentsParagraph[]>} */
    static statusListeners = {};

    /**
     * @param {Object<number, number>}           statuses
     * @param {Object<number, AMENDMENT_VERSION>} versions
     * @param {Object<number, VotingData>}        votingData
     */
    static init(statuses, versions, votingData) {
        AmendmentStatuses.statuses = statuses;
        AmendmentStatuses.versions = versions;
        AmendmentStatuses.votingData = votingData;

        Object.keys(statuses).forEach(amendmentId => {
            AmendmentStatuses.statusListeners[amendmentId] = [];
        });
    }

    /**
     * @param {number}           amendmentId
     * @param {number}           status
     * @param {AMENDMENT_VERSION} version
     * @param {VotingData}       votingData
     */
    static registerNewAmendment(amendmentId, status, version, votingData) {
        AmendmentStatuses.statuses[amendmentId] = status;
        AmendmentStatuses.versions[amendmentId] = version;
        AmendmentStatuses.votingData[amendmentId] = votingData;
        AmendmentStatuses.statusListeners[amendmentId] = [];

        console.log("registered new amendment status", AmendmentStatuses.statuses, AmendmentStatuses.versions, AmendmentStatuses.votingData);
    }

    /**
     * @param {number} amendmentId
     */
    static deleteAmendment(amendmentId) {
        delete AmendmentStatuses.statuses[amendmentId];
        delete AmendmentStatuses.versions[amendmentId];
        delete AmendmentStatuses.votingData[amendmentId];
    }

    /**
     * @param {number} amendmentId
     * @returns {number}
     */
    static getAmendmentStatus(amendmentId) {
        return AmendmentStatuses.statuses[amendmentId];
    }

    /**
     * @param {number} amendmentId
     * @returns {AMENDMENT_VERSION}
     */
    static getAmendmentVersion(amendmentId) {
        return AmendmentStatuses.versions[amendmentId];
    }

    /**
     * @param {number} amendmentId
     * @returns {VotingData}
     */
    static getAmendmentVotingData(amendmentId) {
        return AmendmentStatuses.votingData[amendmentId];
    }

    /**
     * @param {number}                          amendmentId
     * @param {MotionMergeAmendmentsParagraph}  paragraph
     */
    static registerParagraph(amendmentId, paragraph) {
        AmendmentStatuses.statusListeners[amendmentId].push(paragraph);
    }

    /**
     * @param {number} amendmentId
     * @param {number} status
     */
    static setStatus(amendmentId, status) {
        AmendmentStatuses.statuses[amendmentId] = status;
        AmendmentStatuses.statusListeners[amendmentId].forEach(paragraph => {
            paragraph.onAmendmentStatusChanged(amendmentId, status);
        });
    }

    /**
     * @param {number}           amendmentId
     * @param {AMENDMENT_VERSION} version
     */
    static setVersion(amendmentId, version) {
        AmendmentStatuses.versions[amendmentId] = version;
        AmendmentStatuses.statusListeners[amendmentId].forEach(paragraph => {
            paragraph.onAmendmentVersionChanged(amendmentId, version);
        });
    }

    /**
     * @param {number}     amendmentId
     * @param {VotingData} voteData
     */
    static setVotesData(amendmentId, voteData) {
        AmendmentStatuses.votingData[amendmentId] = voteData;
        AmendmentStatuses.statusListeners[amendmentId].forEach(paragraph => {
            paragraph.onAmendmentVotingChanged(amendmentId, voteData);
        });
    }

    /**
     * @returns {number[]}
     */
    static getAmendmentIds() {
        return Object.keys(AmendmentStatuses.statuses).map(key => parseInt(key, 10));
    }

    /**
     * @returns {Object<number, number>}
     */
    static getAllStatuses() {
        return AmendmentStatuses.statuses;
    }

    /**
     * @returns {Object<number, AMENDMENT_VERSION>}
     */
    static getAllVersions() {
        return AmendmentStatuses.versions;
    }

    /**
     * @returns {Object<number, VotingData>}
     */
    static getAllVotingData() {
        return AmendmentStatuses.votingData;
    }
}

// ============================================================
// MotionMergeChangeTooltip
// ============================================================

class MotionMergeChangeTooltip {
    /** @type {JQuery} */
    $element;

    /** @type {MotionMergeAmendmentsTextarea} */
    parent;

    /**
     * @param {JQuery}                          $element
     * @param {number}                          mouseX
     * @param {number}                          mouseY
     * @param {MotionMergeAmendmentsTextarea}   parent
     */
    constructor($element, mouseX, mouseY, parent) {
        this.$element = $element;
        this.parent = parent;

        /** @type {number|null} */
        let positionX = null;
        /** @type {number|null} */
        let positionY = null;

        $element.popover({
            'container': 'body',
            'animation': false,
            'trigger': 'manual',
            'placement': function (popover) {
                const $popover = $(/** @type {any} */ (popover));
                $popover.data("element", $element);
                window.setTimeout(() => {
                    const width = $popover.width(),
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
        const $popover = $element.find("> .popover");
        $popover.on("mousemove", (ev) => {
            ev.stopPropagation();
        });
        window.setTimeout(this.removePopupIfInactive.bind(this), 1000);
    }

    getContent() {
        const $myEl = this.$element;
        let cid = $myEl.data("cid");
        const isAppendedCollision = ($myEl.data("appended-collision") === 1 || $myEl.parent().data("appended-collision") === 1),
            isModU = $myEl.data("is-modu") === 1;

        if (cid === undefined) {
            cid = $myEl.parent().data("cid");
        }
        $myEl.parents(".texteditor").first().find("[data-cid=" + cid + "]").addClass("hover");

        let html = '';
        if (isAppendedCollision) {
            html += '<div class="mergingPopoverCollisionHint">⚠️ ' + translateDirective.getTranslation("amend", "mergedCollisionHint") + '</div>';
        }
        html += '<div class="mergingPopoverButtons">';
        html += '<button type="button" class="accept btn btn-sm btn-default"></button>';
        html += '<button type="button" class="reject btn btn-sm btn-default"></button>';
        html += '<a href="#" class="btn btn-small btn-default opener" target="_blank"><span class="glyphicon glyphicon-new-window"></span></a>';
        html += '<div class="initiator" style="font-size: 0.8em;"></div>';
        html += '</div>';

        const $el = $(html);
        $el.find(".opener").attr("href", $myEl.data("link")).attr("title", translateDirective.getTranslation("amend", "title_open_in_blank"));
        if (isModU) {
            $el.find(".initiator").text(translateDirective.getTranslation("amend", "modU"));
        } else {
            $el.find(".initiator").text(translateDirective.getTranslation("amend", "initiated_by") + ": " + $myEl.data("username"));
        }

        if ($myEl.hasClass("ice-ins")) {
            $el.find("button.accept").text(translateDirective.getTranslation("amend", "change_accept")).on("click", this.accept.bind(this));
            $el.find("button.reject").text(translateDirective.getTranslation("amend", "change_reject")).on("click", this.reject.bind(this));
        } else if ($myEl.hasClass("ice-del")) {
            $el.find("button.accept").text(translateDirective.getTranslation("amend", "change_accept")).on("click", this.accept.bind(this));
            $el.find("button.reject").text(translateDirective.getTranslation("amend", "change_reject")).on("click", this.reject.bind(this));
        } else if ($myEl[0].nodeName.toLowerCase() === 'li') {
            const $list = $myEl.parent();
            if ($list.hasClass("ice-ins")) {
                $el.find("button.accept").text(translateDirective.getTranslation("amend", "change_accept")).on("click", this.accept.bind(this));
                $el.find("button.reject").text(translateDirective.getTranslation("amend", "change_reject")).on("click", this.reject.bind(this));
            } else if ($list.hasClass("ice-del")) {
                $el.find("button.accept").text(translateDirective.getTranslation("amend", "change_accept")).on("click", this.accept.bind(this));
                $el.find("button.reject").text(translateDirective.getTranslation("amend", "change_reject")).on("click", this.reject.bind(this));
            } else {
                console.log("unknown", $list);
            }
        } else {
            console.log("unknown", $myEl);
            alert("unknown");
        }
        return $el;
    }

    removePopupIfInactive() {
        if (this.$element.is(":hover")) {
            return window.setTimeout(this.removePopupIfInactive.bind(this), 1000);
        }
        if ($("body").find(".popover:hover").length > 0) {
            return window.setTimeout(this.removePopupIfInactive.bind(this), 1000);
        }
        this.destroy();
    }

    affectedChangesets() {
        let cid = this.$element.data("cid");
        if (cid === undefined) {
            cid = this.$element.parent().data("cid");
        }
        return this.$element.parents(".texteditor").find("[data-cid=" + cid + "]");
    }

    /**
     * @param {Function} action
     */
    performActionWithUI(action) {
        const scrollX = window.scrollX,
            scrollY = window.scrollY;

        this.parent.saveEditorSnapshot();
        this.destroy();
        action.call(this);
        this.parent.focusTextarea();

        window.scrollTo(scrollX, scrollY);
    }

    accept() {
        this.performActionWithUI(() => {
            this.affectedChangesets().each((i, el) => {
                MotionMergeChangeActions.accept(el, () => {
                    this.parent.onChanged();
                });
            });
        });
    }

    reject() {
        this.performActionWithUI(() => {
            this.affectedChangesets().each((i, el) => {
                MotionMergeChangeActions.reject(el, () => {
                    this.parent.onChanged();
                });
            });
        });
    }

    destroy() {
        this.$element.popover("hide").popover("destroy");

        let cid = this.$element.data("cid");
        if (cid === undefined) {
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
            // ignore
        }
    }
}

// ============================================================
// MotionMergeAmendmentsTextarea
// ============================================================

class MotionMergeAmendmentsTextarea {
    /** @type {CKEDITOR.editor} */
    texteditor;

    /** @type {string|null} */
    unchangedText = null;

    /** @type {boolean} */
    hasChanged = false;

    /** @type {Array<function(): void>} */
    changedListeners = [];

    /** @type {JQuery} */
    $holder;

    /** @type {JQuery} */
    $changedIndicator;

    /** @type {JQuery} */
    $mergeActionHolder;

    /**
     * @param {JQuery} $holder
     * @param {JQuery} $changedIndicator
     * @param {JQuery} $mergeActionHolder
     */
    constructor($holder, $changedIndicator, $mergeActionHolder) {
        this.$holder = $holder;
        this.$changedIndicator = $changedIndicator;
        this.$mergeActionHolder = $mergeActionHolder;

        const $textarea = $holder.find(".texteditor");
        const edit = new AntragsgruenEditor($textarea.attr("id"));
        this.texteditor = edit.getEditor();

        this.setText(this.texteditor.getData());

        if ($holder.data("unchanged")) {
            this.unchangedText = $holder.data("unchanged");
            this.onChanged();
        }

        this.texteditor.on('change', this.onChanged.bind(this));
    }

    /**
     * @param {string} html
     */
    prepareText(html) {
        const $text = $('<div>' + html + '</div>');

        // Move the amendment-Data from OL's and UL's to their list items
        $text.find("ul.appendHint, ol.appendHint").each((i, el) => {
            const $this = $(el),
                appendHint = $this.data("append-hint");
            $this.find("> li").addClass("appendHint").attr("data-append-hint", appendHint)
                .attr("data-link", $this.data("link"))
                .attr("data-username", $this.data("username"));
            $this.removeClass("appendHint").removeData("append-hint");
        });

        // Remove double markup
        $text.find(".moved .moved").removeClass('moved');
        $text.find(".moved").each(this.markupMovedParagraph.bind(this));

        const newText = $text.html();
        this.texteditor.setData(newText);
        this.unchangedText = this.normalizeHtml(this.texteditor.getData());
        this.texteditor.fire('saveSnapshot');
        this.onChanged();
    }

    /**
     * @param {function(): void} cb
     */
    addChangedListener(cb) {
        this.changedListeners.push(cb);
    }

    /**
     * @param {number}  _
     * @param {Element} el
     */
    markupMovedParagraph(_, el) {
        let $node = $(el);
        const paragraphNew = $node.data('moving-partner-paragraph');
        /** @type {string} */
        let msg;

        if ($node.hasClass('inserted')) {
            msg = translateDirective.getTranslation("motion", 'moved_paragraph_from');
        } else {
            msg = translateDirective.getTranslation("motion", 'moved_paragraph_to');
        }
        msg = msg.replace(/##PARA##/, (paragraphNew + 1));

        if ($node[0].nodeName === 'LI') {
            $node = $node.parent();
        }

        $node.attr("data-moving-msg", msg);
    }

    initializeTooltips() {
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

    acceptAll() {
        this.saveEditorSnapshot();
        this.$holder.find(".ice-ins").each((i, el) => {
            MotionMergeChangeActions.insertAccept(el);
        });
        this.$holder.find(".ice-del").each((i, el) => {
            MotionMergeChangeActions.deleteAccept(el, false);
        });
        this.onChanged();
        window.setTimeout(() => {
            // Wait for animation -> remove "all dropdown"
            this.onChanged();
            this.saveEditorSnapshot();
        }, 1000);
    }

    rejectAll() {
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

    saveEditorSnapshot() {
        this.texteditor.fire('saveSnapshot');
    }

    focusTextarea() {
        //this.$holder.find(".texteditor").focus();
        // This lead to strange cursor behavior, e.g. when removing a colliding paragraph
    }

    /**
     * @returns {string}
     */
    getContent() {
        return this.texteditor.getData();
    }

    /**
     * @returns {string}
     */
    getUnchangedContent() {
        return this.unchangedText;
    }

    /**
     * @param {string} html
     */
    setText(html) {
        this.prepareText(html);
        this.initializeTooltips();
    }

    /**
     * @param {string} html
     * @returns {string}
     */
    normalizeHtml(html) {
        /** @type {Object<string, string>} */
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
            '&ldquo;': '"',
            '&bull;': '•',
            '&sect;': '§',
            '&eacute;': 'é',
            '&rsquo;': '\u2019',
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

    onChanged() {
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

    /**
     * @returns {boolean}
     */
    hasChanges() {
        return this.hasChanged;
    }
}

// ============================================================
// MotionMergeAmendmentsParagraph
// ============================================================

class MotionMergeAmendmentsParagraph {
    /** @type {number} */
    sectionId;

    /** @type {number} */
    paragraphId;

    /** @type {MotionMergeAmendmentsTextarea} */
    textarea;

    /** @type {boolean} */
    hasUnsavedChanges = false;

    /** @type {number[]} */
    handledCollisions = [];

    /** @type {any} */
    statusWidget;

    /** @type {any} */
    statusWidgetComponent;

    /** @type {JQuery} */
    $holder;

    /**
     * @param {JQuery} $holder
     * @param {any}    draft
     * @param {any}    amendmentStaticData
     */
    constructor($holder, draft, amendmentStaticData) {
        this.$holder = $holder;
        this.sectionId = parseInt($holder.data('sectionId'));
        this.paragraphId = parseInt($holder.data('paragraphId'));

        const paragraphDraft = draft.paragraphs && draft.paragraphs[this.sectionId + "_" + this.paragraphId]
            ? draft.paragraphs[this.sectionId + "_" + this.paragraphId]
            : null;
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

        $holder.find(".amendmentStatus").each((i, element) => {
            AmendmentStatuses.registerParagraph($(element).data("amendment-id"), this);
        });

        this.textarea.addChangedListener(() => this.hasUnsavedChanges = true);
    }

    /**
     * @param {any} amendmentStaticData
     */
    initStatusWidget(amendmentStaticData) {
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
                bootbox.confirm(translateDirective.getTranslation('amend', 'reload_paragraph'), (result) => {
                    if (result) {
                        cb();
                    }
                });
            } else {
                cb();
            }
        };

        para.statusWidget = createApp({
            render() {
                const ParagraphAmendmentSettings = resolveComponent('paragraph-amendment-settings');
                return h('div', { class: 'statuses' },
                    this.amendmentParagraphData.map(data =>
                        h(ParagraphAmendmentSettings, {
                            amendment: data.amendment,
                            nameBase: data.nameBase,
                            idAdd: data.idAdd,
                            active: data.active,
                            status: data.status,
                            version: data.version,
                            votingData: data.votingData,
                            onUpdate: ($event) => this.update($event),
                        })
                    )
                );
            },
            data() {
                return { amendmentParagraphData };
            },
            methods: {
                getAllAmendmentData() {
                    return this.amendmentParagraphData;
                },
                getAmendmentData(amendmentId) {
                    for (let i = 0; i < this.amendmentParagraphData.length; i++) {
                        if (this.amendmentParagraphData[i].amendmentId === amendmentId) {
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
                            console.log("set version", eventData[2]);
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
                    this.amendmentParagraphData = this.amendmentParagraphData.filter(amend => amend.amendmentId !== amendmentId);
                }
            }
        });

        para.statusWidget.component("paragraph-amendment-settings", ParagraphAmendmentSettings);
        para.statusWidget.directive("t", translateDirective);

        para.statusWidgetComponent = para.statusWidget.mount(this.$holder.find(".changeToolbar .statuses")[0]);
    }

    /**
     * @param {number}           amendmentId
     * @param {string}           version
     */
    onAmendmentVersionChanged(amendmentId, version) {
        this.statusWidgetComponent.onVersionUpdated(amendmentId, version);
    }

    /**
     * @param {number}     amendmentId
     * @param {VotingData} votingData
     */
    onAmendmentVotingChanged(amendmentId, votingData) {
        this.statusWidgetComponent.onVotingUpdated(amendmentId, votingData);
    }

    /**
     * @param {number} amendmentId
     * @param {number} status
     */
    onAmendmentStatusChanged(amendmentId, status) {
        this.statusWidgetComponent.onStatusUpdated(amendmentId, status);
    }

    /**
     * @param {any}    amendment
     * @param {any}    nameBase
     * @param {any}    idAdd
     * @param {any}    active
     * @param {any}    status
     * @param {any}    verstion
     * @param {any}    votingData
     */
    onAmendmentAdded(amendment, nameBase, idAdd, active, status, verstion, votingData) {
        this.statusWidgetComponent.onAmendmentAdded(amendment, nameBase, idAdd, active, status, verstion, votingData);
    }

    /**
     * @param {number} amendmentId
     */
    onAmendmentDeleted(amendmentId) {
        this.statusWidgetComponent.onAmendmentDeleted(amendmentId);
    }

    initSetCollisionsAsHandled() {
        this.$holder.on("click", "button.hideCollision", (ev) => {
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

    initButtons() {
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

    reloadText() {
        const amendments = this.statusWidgetComponent.getAllAmendmentData()
            .filter(amendmentData => amendmentData.active)
            .map(amendmentData => ({
                id: amendmentData.amendmentId,
                version: AmendmentStatuses.getAmendmentVersion(amendmentData.amendmentId),
            }));
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

    getDraftData() {
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

    onDraftChanged() {
        this.hasUnsavedChanges = false;
    }
}

// ============================================================
// MotionMergeAmendments (exported singleton)
// ============================================================

export class MotionMergeAmendments {
    /** @type {MotionMergeChangeTooltip|null} */
    static activePopup = null;

    /** @type {number|null} */
    static currMouseX = null;

    /** @type {JQuery} */
    static $form;

    /** @type {JQuery} */
    $draftSavingPanel;

    /** @type {JQuery} */
    $newAmendmentAlert;

    /** @type {Object<string, MotionMergeAmendmentsParagraph>} */
    paragraphsByTypeAndNo = {};

    /** @type {boolean} */
    hasUnsavedChanges = false;

    /**
     * @param {HTMLElement} form
     */
    constructor(form) {
        MotionMergeAmendments.$form = $(form);

        const draft = JSON.parse(document.getElementById('mergeDraft').getAttribute('value'));
        AmendmentStatuses.init(draft.amendmentStatuses, draft.amendmentVersions, draft.amendmentVotingData);

        const amendmentStaticData = MotionMergeAmendments.$form.data('amendment-static-data');

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

    /**
     * @returns {string}
     */
    static onLeavePage() {
        return translateDirective.getTranslation("amend", "leave_changed_page");
    }

    /**
     * @param {Date} date
     */
    setDraftDate(date) {
        this.$draftSavingPanel.find(".lastSaved .none").hide();

        /** @type {Intl.DateTimeFormatOptions} */
        const options = {
            year: 'numeric', month: 'numeric', day: 'numeric',
            hour: 'numeric', minute: 'numeric',
            hour12: false
        };
        const lang = /** @type {string} */ ($("html").attr("lang"));
        const formatted = new Intl.DateTimeFormat(lang, options).format(date);

        this.$draftSavingPanel.find(".lastSaved .value").text(formatted);
    }

    initRemovingSectionTexts() {
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

    initProtocol() {
        const $textarea = $("#protocol_text_wysiwyg");
        $textarea.attr("contenteditable", "true");
        const ckeditor = new AntragsgruenEditor($textarea.attr("id"));
        const editor = ckeditor.getEditor();

        $textarea.parents("form").on("submit", () => {
            $textarea.parent().find("textarea").val(editor.getData());
        });
    }

    /**
     * @param {boolean} [onlyInput=false]
     */
    saveDraft(onlyInput = false) {
        if (Object.keys(this.paragraphsByTypeAndNo).map(id => this.paragraphsByTypeAndNo[id])
            .filter(par => par.hasUnsavedChanges).length === 0 && !this.hasUnsavedChanges) {
            return;
        }

        console.log("Has unsaved changes");

        const protocolPublic = /** @type {string} */ ($("input[name=protocol_public]:checked").val());
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
            data.removedSections.push(parseInt(/** @type {string} */ ($(el).val())));
        });

        Object.keys(this.paragraphsByTypeAndNo).forEach(paraId => {
            data.paragraphs[paraId] = this.paragraphsByTypeAndNo[paraId].getDraftData();
        });
        const isPublic = /** @type {boolean} */ (this.$draftSavingPanel.find('input[name=public]').prop('checked'));

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

    initAutosavingDraft() {
        const $toggle = this.$draftSavingPanel.find('input[name=autosave]');

        window.setInterval(() => {
            if ($toggle.prop('checked')) {
                this.saveDraft(false);
            }
        }, 5000);

        if (localStorage) {
            const state = localStorage.getItem('merging-draft-auto-save');
            if (state !== null) {
                $toggle.prop('checked', (state === '1'));
            }
        }
        $toggle.on("change", () => {
            const active = /** @type {boolean} */ ($toggle.prop('checked'));
            if (localStorage) {
                localStorage.setItem('merging-draft-auto-save', (active ? '1' : '0'));
            }
        }).trigger('change');
    }

    initDraftSaving() {
        this.$draftSavingPanel = MotionMergeAmendments.$form.find('#draftSavingPanel');
        this.$draftSavingPanel.find('.saveDraft').on('click', () => {
            this.hasUnsavedChanges = true;
            this.saveDraft(false);
        });
        this.$draftSavingPanel.find('input[name=public]').on('change', () => {
            this.hasUnsavedChanges = true;
            this.saveDraft(false);
        });
        this.initAutosavingDraft();

        if (this.$draftSavingPanel.data("resumed-date")) {
            const date = new Date(this.$draftSavingPanel.data("resumed-date"));
            this.setDraftDate(date);
        }

        $(".sectionType0").on("change", () => this.hasUnsavedChanges = true);

        $("#yii-debug-toolbar").remove();
    }

    initNewAmendmentAlert() {
        this.$newAmendmentAlert = MotionMergeAmendments.$form.find('#newAmendmentAlert');
        this.$newAmendmentAlert.find('.closeLink').on('click', () => {
            this.$newAmendmentAlert.find('.buttons').children().remove();
            this.$newAmendmentAlert.removeClass('revealed');
            window.setTimeout(() => {
                this.$newAmendmentAlert.addClass('hidden');
            }, 1000);
        });
    }

    /**
     * @param {number} amendmentId
     * @param {string} title
     */
    alertAboutNewAmendment(amendmentId, title) {
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

    initCheckBackendStatus() {
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

    /**
     * @param {any[]} newAmendments
     * @param {any[]} deletedAmendments
     */
    onReceivedBackendStatus(newAmendments, deletedAmendments) {
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
                    paraObj.onAmendmentAdded(paraAmendmentData, data['nameBase'], data['idAdd'], data['active'], status['status'], status['version'], status['votingData']);
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
