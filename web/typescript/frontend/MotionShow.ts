/// <reference path="../typings/jquery-typings.d.ts" />

import '../shared/MotionInitiatorShow';
import { LineNumberHighlighting } from "./LineNumberHighlighting";

class MotionParagraph {
    private activeAmendmentId: number = null;
    private $paraFirstLine: JQuery;
    private readonly lineHeight: number;

    constructor(private $element: JQuery) {
        this.$paraFirstLine = $element.find(".lineNumber").first();
        this.lineHeight = this.$paraFirstLine.height();

        let amends = $element.find(".bookmarks > .amendment");
        amends = amends.sort(function (el1, el2) {
            return $(el1).data("first-line") - $(el2).data("first-line");
        });
        $element.find(".bookmarks").append(amends);

        $element.find('ul.bookmarks li.amendment').each((num, el) => {
            this.initInlineAmendmentPosition($(el));
            this.toggleInlineAmendmentBehavior($(el));
        });
    }

    private initInlineAmendmentPosition($amendment: JQuery) {
        let firstLine = $amendment.data("first-line"),
            targetOffset = (firstLine - this.$paraFirstLine.data("line-number")) * this.lineHeight,
            $prevBookmark = $amendment.prevAll(),
            delta = targetOffset;
        $prevBookmark.each(function () {
            let $pre = $(this);
            delta -= $pre.height();
            delta -= parseInt($pre.css("margin-top"));
            delta -= 7;
        });
        if (delta < 0) {
            delta = 0;
        }
        $amendment.css('margin-top', delta + "px");
    }

    private showInlineAmendment(amendmentId: number) {
        if (this.activeAmendmentId) {
            this.hideInlineAmendment(this.activeAmendmentId);
        }
        this.$element.find("> .textOrig").addClass("hidden");
        this.$element.find("> .textAmendment").addClass("hidden");
        this.$element.find("> .textAmendment.amendment" + amendmentId).removeClass("hidden");
        this.$element.find(".bookmarks .amendment" + amendmentId).find("a").addClass('active');
        this.activeAmendmentId = amendmentId;
    }

    private hideInlineAmendment(amendmentId: number) {
        this.$element.find("> .textOrig").removeClass("hidden");
        this.$element.find("> .textAmendment").addClass("hidden");
        this.$element.find(".bookmarks .amendment" + amendmentId).find("a").removeClass('active');
        this.activeAmendmentId = null;
    }


    private toggleInlineAmendmentBehavior($amendment: JQuery) {
        const $link = $amendment.find("a"),
            amendmentId = $link.data("id");
        if ($("html").hasClass("touchevents")) {
            $link.click((ev) => {
                ev.preventDefault();
                if (this.$element.find("> .textAmendment.amendment" + amendmentId).hasClass("hidden")) {
                    this.showInlineAmendment(amendmentId)
                } else {
                    this.hideInlineAmendment(amendmentId);
                }
            });
        } else {
            $amendment.mouseover(() => {
                this.showInlineAmendment(amendmentId);
            }).mouseout(() => {
                this.hideInlineAmendment(amendmentId);
            });
        }
    }
}

class MotionShow {
    constructor() {
        new MotionInitiatorShow();
        new LineNumberHighlighting();

        let $paragraphs = $('.motionTextHolder .paragraph');
        $paragraphs.find('.comment .shower').click(this.showComment.bind(this));
        $paragraphs.find('.comment .hider').click(this.hideComment.bind(this));

        $paragraphs.filter('.commentsOpened').find('.comment .shower').click();
        $paragraphs.filter(':not(.commentsOpened)').find('.comment .hider').click();

        $paragraphs.each((i, el) => {
            new MotionParagraph($(el));
        });


        $('.tagAdderHolder').click(function (ev) {
            ev.preventDefault();
            $(this).addClass("hidden");
            $('#tagAdderForm').removeClass("hidden");
        });

        let s = location.hash.split('#comm');
        if (s.length == 2) {
            $('#comment' + s[1]).scrollintoview({top_offset: -100});
        }

        $("form.delLink").submit(this.delSubmit.bind(this));
        $(".share_buttons a").click(this.shareLinkClicked.bind(this));

        this.markMovedParagraphs();
        this.initPrivateComments();
        this.initCmdEnterSubmit();
    }

    private markMovedParagraphs() {
        // Remove double markup
        $(".motionTextHolder .moved .moved").removeClass('moved');

        $(".motionTextHolder .moved").each(function () {
            let $node = $(this),
                paragraphNew = $node.data('moving-partner-paragraph'),
                sectionId = $node.parents('.paragraph').first().attr('id').split('_')[1],
                paragraphNewFirstline = $('#section_' + sectionId + '_' + paragraphNew).find('.lineNumber').first().data('line-number'),
                msg: string;

            if ($node.hasClass('inserted')) {
                msg = __t('std', 'moved_paragraph_from_line');
            } else {
                msg = __t('std', 'moved_paragraph_to_line');
            }
            msg = msg.replace(/##LINE##/, paragraphNewFirstline).replace(/##PARA##/, (paragraphNew + 1));

            if ($node[0].nodeName === 'LI') {
                $node = $node.parent();
            }
            let $msg = $('<div class="movedParagraphHint"></div>');
            $msg.text(msg);
            $msg.insertBefore($node);
        });
    }

    private initPrivateComments() {
        if ($('.privateParagraph, .privateNote').length > 0) {
            $('.privateParagraphNoteOpener').removeClass('hidden');
        }
        $('.privateNoteOpener').click(() => {
            $('.privateNoteOpener').remove();
            $('.motionData .privateNotes').removeClass('hidden');
            $('.motionData .privateNotes textarea').focus();
            $('.privateParagraphNoteOpener').removeClass('hidden');
        });
        $('.privateParagraphNoteOpener').click((ev) => {
            $(ev.currentTarget).addClass('hidden');
            const $form = $(ev.currentTarget).parents('.privateParagraphNoteHolder').find('form');
            $form.removeClass('hidden');
            $form.find('textarea').focus();
        });
        $('.privateNotes blockquote').click(() => {
            $('.privateNotes blockquote').addClass('hidden');
            $('.privateNotes form').removeClass('hidden');
            $('.privateNotes textarea').focus();
        });
        $('.privateParagraphNoteHolder blockquote').click((ev) => {
            const $target = $(ev.currentTarget).parents('.privateParagraphNoteHolder');
            $target.find('blockquote').addClass('hidden');
            $target.find('form').removeClass('hidden');
            $target.find('textarea').focus();
        });
    }

    private delSubmit(ev) {
        ev.preventDefault();
        let form = ev.target;
        bootbox.confirm(__t("std", "del_confirm"), (result) => {
            if (result) {
                form.submit();
            }
        });
    }

    private shareLinkClicked(ev) {
        let target = $(ev.currentTarget).attr("href");
        if (window.open(target, '_blank', 'width=600,height=460')) {
            ev.preventDefault();
        }
    }

    private showComment(ev) {
        ev.preventDefault();
        const $node = $(ev.currentTarget),
            $commentHolder = $node.parents('.paragraph').first().find('.commentHolder'),
            $bookmark = $node.parent();
        $node.addClass('hidden');
        $bookmark.find('.hider').removeClass('hidden');
        $commentHolder.removeClass('hidden');
        if (!$commentHolder.isOnScreen(0.1, 0.1)) {
            $commentHolder.scrollintoview({top_offset: -100});
        }
    }

    private hideComment(ev) {
        const $node = $(ev.currentTarget),
            $bookmark = $node.parent();
        $node.addClass('hidden');
        $bookmark.find('.shower').removeClass('hidden');

        $node.parents('.paragraph').first().find('.commentHolder').addClass('hidden');
        ev.preventDefault();
    }

    private initCmdEnterSubmit() {
        $(document).on('keypress', 'form textarea', (ev) => {
            if (ev.originalEvent['metaKey'] && ev.originalEvent['keyCode'] === 13) {
                let $textarea = $(ev.currentTarget);
                $textarea.parents("form").first().find("button[type=submit]").trigger("click");
            }
        });
    }
}

new MotionShow();
