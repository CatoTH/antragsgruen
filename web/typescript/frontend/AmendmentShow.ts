import '../shared/MotionInitiatorShow';

class AmendmentShow {
    constructor() {
        new MotionInitiatorShow();

        $("form.delLink").submit(this.delSubmit.bind(this));
        $(".share_buttons a").click(this.shareLinkClicked.bind(this));

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
                form.submit();
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
        $('.privateNoteOpener').click(() => {
            $('.privateNoteOpener').remove();
            $('.motionData .privateNotes').removeClass('hidden');
            $('.motionData .privateNotes textarea').focus();
        });
        $('.privateNotes blockquote').click(() => {
            $('.privateNotes blockquote').addClass('hidden');
            $('.privateNotes form').removeClass('hidden');
            $('.privateNotes textarea').focus();
        });
    }
}

new AmendmentShow();
