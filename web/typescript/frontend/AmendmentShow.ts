import '../shared/MotionInitiatorShow';

class AmendmentShow {
    constructor() {
        new MotionInitiatorShow();

        $("form.delLink").on("submit", this.delSubmit.bind(this));
        $(".share_buttons a").on("click", this.shareLinkClicked.bind(this));

        let s: string[] = location.hash.split('#comm');
        if (s.length == 2) {
            $('#comment' + s[1]).scrollintoview({top_offset: -100});
        }

        this.initPrivateComments();
        this.initCmdEnterSubmit();
    }

    private delSubmit(ev) {
        ev.preventDefault();
        let form: JQuery = ev.target;
        bootbox.confirm(__t("std", "del_confirm"), function (result) {
            if (result) {
                form.trigger("submit");
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
}

new AmendmentShow();
