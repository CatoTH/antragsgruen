// @ts-check

import { IMotionShow } from "../shared/IMotionShow.js"
import { LineNumberHighlighting } from "./LineNumberHighlighting.js";

/** @typedef {import("jquery").JQuery<HTMLElement>} JQueryEl */

/**
 * MotionParagraph handles inline amendments inside a paragraph
 */
class MotionParagraph {

    /** @type {number|null} */
    activeAmendmentId = null;

    /** @type {JQueryEl} */
    $paraFirstLine;

    /** @type {number} */
    lineHeight;

    /** @type {JQueryEl} */
    $element;

    /**
     * @param {HTMLElement} element
     */
    constructor(element) {
        const $element = $(element);
        this.$element = $element;
        this.$paraFirstLine = $element.find(".lineNumber").first();
        this.lineHeight = this.$paraFirstLine.height();

        // Sort amendments by first line
        let amends = $element.find(".bookmarks > .amendment").toArray();
        amends.sort((el1, el2) => $(el1).data("first-line") - $(el2).data("first-line"));
        $element.find(".bookmarks").append(amends);

        // Initialize each amendment
        $element.find('ul.bookmarks li.amendment').each((_, el) => {
            const $amendment = $(el);
            this.initInlineAmendmentPosition($amendment);
            this.toggleInlineAmendmentBehavior($amendment);
        });
    }

    /** @param {JQueryEl} $amendment */
    initInlineAmendmentPosition = ($amendment) => {
        const firstLine = $amendment.data("first-line");
        let delta = (firstLine - this.$paraFirstLine.data("line-number")) * this.lineHeight;

        $amendment.prevAll().each((_, el) => {
            const $pre = $(el);
            delta -= $pre.height();
            delta -= parseInt($pre.css("margin-top"));
            delta -= 7;
        });

        if (delta < 0) delta = 0;
        $amendment.css('margin-top', delta + "px");
    }

    showInlineAmendment = (amendmentId) => {
        if (this.activeAmendmentId) this.hideInlineAmendment(this.activeAmendmentId);

        this.$element.find("> .textOrig").addClass("hidden");
        this.$element.find("> .textAmendment").addClass("hidden");
        this.$element.find("> .textAmendment.amendment" + amendmentId).removeClass("hidden");
        this.$element.find(".bookmarks .amendment" + amendmentId).find("a").addClass('active');

        this.activeAmendmentId = amendmentId;
    }

    hideInlineAmendment = (amendmentId) => {
        this.$element.find("> .textOrig").removeClass("hidden");
        this.$element.find("> .textAmendment").addClass("hidden");
        this.$element.find(".bookmarks .amendment" + amendmentId).find("a").removeClass('active');
        this.activeAmendmentId = null;
    }

    /** @param {JQueryEl} $amendment */
    toggleInlineAmendmentBehavior = ($amendment) => {
        const $link = $amendment.find("a");
        const amendmentId = $link.data("id");

        const hasHover = window.matchMedia && window.matchMedia("(hover: hover)").matches;
        if (hasHover) {
            $amendment.on("mouseover", () => this.showInlineAmendment(amendmentId))
                .on("mouseout", () => this.hideInlineAmendment(amendmentId));
        } else {
            $link.on("click", (ev) => {
                ev.preventDefault();
                const isHidden = this.$element.find("> .textAmendment.amendment" + amendmentId).hasClass("hidden");
                if (isHidden) this.showInlineAmendment(amendmentId);
                else this.hideInlineAmendment(amendmentId);
            });
        }
    }
}


/**
 * MotionShow manages the whole motion text page, comments, and amendments
 */
export class MotionShow {

    constructor() {
        new LineNumberHighlighting();

        const $paragraphs = $('.motionTextHolder .paragraph');

        // Comment buttons
        $paragraphs.find('.comment .shower').on("click", this.showComment);
        $paragraphs.find('.comment .hider').on("click", this.hideComment);

        $paragraphs.filter('.commentsOpened').find('.comment .shower').trigger("click");
        $paragraphs.filter(':not(.commentsOpened)').find('.comment .hider').trigger("click");

        // Initialize MotionParagraph for each paragraph
        $paragraphs.each((_, el) => new MotionParagraph(el));

        // Handle URL hash scroll
        this.scrollFromHash();

        // Other initializations
        this.markMovedParagraphs();
        this.initPrivateComments();
        this.initProtocolShower();

        const common = new IMotionShow();
        common.initContactShow();
        common.initAgreeToProposal();
        common.initAmendmentTextMode();
        common.initCmdEnterSubmit();
        common.initDelSubmit();
        common.initDataTableActions();
        common.initExpandableList();
    }

    scrollFromHash = () => {
        const sComm = window.location.hash.split('#comm');
        if (sComm.length === 2) $('#comment' + sComm[1]).scrollintoview({top_offset: -100});

        const sNote = window.location.hash.split('#note');
        if (sNote.length === 2) $('#privatenote' + sNote[1]).scrollintoview({top_offset: -100});

        const sAmend = window.location.hash.split('#amendment');
        if (sAmend.length === 2) $(".bookmarks .amendment" + sAmend[1]).first().scrollintoview({top_offset: -100});
    }

    markMovedParagraphs = () => {
        $(".motionTextHolder .moved .moved").removeClass('moved');

        $(".motionTextHolder .moved").each((_, el) => {
            let $node = $(el);
            const paragraphNew = $node.data('moving-partner-paragraph');
            const sectionId = $node.parents('.paragraph').first().attr('id').split('_')[1];
            const paragraphNewFirstline = $('#section_' + sectionId + '_' + paragraphNew).find('.lineNumber').first().data('line-number');

            let msg = $node.hasClass('inserted')
                ? __t('std', 'moved_paragraph_from_line')
                : __t('std', 'moved_paragraph_to_line');

            msg = msg.replace(/##LINE##/, paragraphNewFirstline)
                .replace(/##PARA##/, (paragraphNew + 1));

            if ($node[0].nodeName === 'LI') $node = $node.parent();

            $('<div class="movedParagraphHint"></div>').text(msg).insertBefore($node);
        });
    }

    initProtocolShower = () => {
        $(".motionProtocol .protocolOpener").on("click", () => {
            $(".protocolHolder").removeClass('hidden');
            $(".protocolHolder").scrollintoview({top_offset: -50});
        });
    }

    initPrivateComments() {
        if ($('.privateParagraph, .privateNote').length > 0) $('.privateParagraphNoteOpener').removeClass('hidden');

        $('.privateNoteOpener').on("click", (ev) => {
            ev.preventDefault();
            $('.privateNoteOpener').remove();
            $('.motionData .privateNotes').removeClass('hidden');
            $('.motionData .privateNotes textarea').trigger("focus");
            $('.privateParagraphNoteOpener').removeClass('hidden');
        });

        $('.privateParagraphNoteOpener button').on("click", (ev) => {
            const $btn = $(ev.currentTarget);
            $btn.parents(".privateParagraphNoteOpener").addClass('hidden');
            const $form = $btn.parents('.privateParagraphNoteHolder').find('form');
            $form.removeClass('hidden').find('textarea').trigger("focus");
        });

        $('.privateNotes blockquote').on("click", () => {
            $('.privateNotes blockquote').addClass('hidden');
            $('.privateNotes form').removeClass('hidden').find('textarea').trigger("focus");
        });

        $('.privateParagraphNoteHolder blockquote').on("click", (ev) => {
            const $target = $(ev.currentTarget).parents('.privateParagraphNoteHolder');
            $target.find('blockquote').addClass('hidden');
            $target.find('form').removeClass('hidden').find('textarea').trigger("focus");
        });
    }

    showComment = (ev) => {
        ev.preventDefault();
        const $node = $(ev.currentTarget);
        const $commentHolder = $node.parents('.paragraph').first().find('.commentHolder');
        const $bookmark = $node.parent();

        $node.addClass('hidden');
        $bookmark.find('.hider').removeClass('hidden');
        $commentHolder.removeClass('hidden');

        if (!$commentHolder.isOnScreen(0.1, 0.1)) {
            $commentHolder.scrollintoview({top_offset: -100});
        }
    }

    hideComment = (ev) => {
        ev.preventDefault();
        const $node = $(ev.currentTarget);
        const $bookmark = $node.parent();

        $node.addClass('hidden');
        $bookmark.find('.shower').removeClass('hidden');
        $node.parents('.paragraph').first().find('.commentHolder').addClass('hidden');
    }
}
