import '../shared/IMotionShow';

class AmendmentShow {
    constructor() {
        let s: string[] = window.location.hash.split('#comm');
        if (s.length == 2) {
            $('#comment' + s[1]).scrollintoview({top_offset: -100});
        }

        this.initPrivateComments();

        const common = new IMotionShow();
        common.initContactShow();
        common.initAmendmentTextMode();
        common.initCmdEnterSubmit();
        common.initDelSubmit();
        common.initDataTableActions();
        common.initExpandableList();
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
