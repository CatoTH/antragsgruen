import '../shared/MotionInitiatorShow';

class AmendmentShow {
    constructor() {
        new MotionInitiatorShow();

        $("form.delLink").on("submit", this.delSubmit.bind(this));
        $(".share_buttons a").on("click", this.shareLinkClicked.bind(this));

        $('.tagAdderHolder').on("click", function (ev) {
            ev.preventDefault();
            $(this).addClass("hidden");
            $('#tagAdderForm').removeClass("hidden");
        });

        let s: string[] = location.hash.split('#comm');
        if (s.length == 2) {
            $('#comment' + s[1]).scrollintoview({top_offset: -100});
        }

        this.initPrivateComments();
        this.initCmdEnterSubmit();
        this.initAmendmentTextMode();
    }

    private delSubmit(ev) {
        ev.preventDefault();
        let form: JQuery = ev.target;
        bootbox.confirm(__t("std", "del_confirm"), function (result) {
            if (result) {
                // noinspection JSDeprecatedSymbols
                form.submit(); // Native submit() function, not the jQuery one
            }
        });
    }

    private shareLinkClicked(ev) {
        let target: string = $(ev.currentTarget).attr("href");
        if (window.open(target, '_blank', 'width=600,height=460')) {
            ev.preventDefault();
        }
    }

    private initCmdEnterSubmit() {
        $(document).on('keypress', 'form textarea', (ev) => {
            if (ev.originalEvent['metaKey'] && ev.originalEvent['keyCode'] === 13) {
                let $textarea = $(ev.currentTarget);
                $textarea.parents("form").first().find("button[type=submit]").trigger("click");
            }
        });
    }

    private initPrivateComments()
    {
        $('.privateNoteOpener button').on("click", (ev) => {
            ev.preventDefault();
            $('.privateNoteOpener').remove();
            $('.motionData .privateNotes').removeClass('hidden');
            $('.motionData .privateNotes textarea').trigger("focus");
        });
        $('.privateNotes blockquote').on("click", () => {
            $('.privateNotes blockquote').addClass('hidden');
            $('.privateNotes form').removeClass('hidden');
            $('.privateNotes textarea').trigger("focus");
        });
    }

    private initAmendmentTextMode()
    {
        $('.amendmentTextModeSelector a.showOnlyChanges').on('click', (ev) => {
            const $section = $(ev.target).parents(".motionTextHolder");
            $section.find(".amendmentTextModeSelector .showOnlyChanges").parent().addClass('selected');
            $section.find(".amendmentTextModeSelector .showFullText").parent().removeClass('selected');
            $section.find(".fullMotionText").addClass('hidden');
            $section.find(".onlyChangedText").removeClass('hidden');
            ev.preventDefault();
        });
        $('.amendmentTextModeSelector a.showFullText').on('click', (ev) => {
            const $section = $(ev.target).parents(".motionTextHolder");
            $section.find(".amendmentTextModeSelector .showOnlyChanges").parent().removeClass('selected');
            $section.find(".amendmentTextModeSelector .showFullText").parent().addClass('selected');
            $section.find(".fullMotionText").removeClass('hidden');
            $section.find(".onlyChangedText").addClass('hidden');
            ev.preventDefault();
        });
    }
}

new AmendmentShow();
