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
}

new AmendmentShow();
