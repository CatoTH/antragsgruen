// @ts-check

export class ResponsibilitySetter {
    /**
     * @param {JQuery} $list
     */
    constructor($list) {
        $list.on("click", ".respHolder .respUser", this.userSelected.bind(this));
        $list.on("click", ".respHolder .respCommentRow button", this.onCommentSaveBtn.bind(this));
        $list.on("keypress", ".respHolder .respCommentRow input[type=text]", this.onKeyPressed.bind(this));
    }

    /** @param {JQuery.TriggeredEvent} ev */
    userSelected(ev) {
        ev.preventDefault();
        const $li = $(ev.currentTarget);
        const $row = $li.parents(".respHolder").first();
        const userId = $li.data("user-id");
        const name = $li.find(".name").text();
        const url = $row.data("save-url");
        $.post(url, {
            '_csrf': $('input[name=_csrf]').val(),
            user: userId
        }, (ret) => {
            if (ret['success']) {
                $row.find(".respUser").removeClass("selected");
                $li.addClass("selected");
                $row.find(".responsibilityUser").text(name).data("user-id", userId);
            } else {
                alert("An error occurred while saving")
            }
        });
    }

    /** @param {JQuery.TriggeredEvent} ev */
    onCommentSaveBtn(ev) {
        ev.preventDefault();
        const $row = $(ev.currentTarget).parents(".respHolder").first();
        this.saveComment($row);
    }

    /** @param {JQuery.KeyPressEvent} ev */
    onKeyPressed(ev) {
        if (ev.key === "Enter") {
            ev.preventDefault();
            ev.stopPropagation();
            const $row = $(ev.currentTarget).parents(".respHolder").first();
            this.saveComment($row);
        }
    }

    /** @param {JQuery} $row */
    saveComment($row) {
        /** @type {string} comment */
        const comment = $row.find(".respCommentRow input[type=text]").val();
        const url = $row.data("save-url");

        $.post(url, {
            '_csrf': $('input[name=_csrf]').val(),
            'comment': comment
        }, (ret) => {
            if (ret['success']) {
                $row.find(".responsibilityComment").text(comment);
                if ($row.hasClass('open')) {
                    $row.find('.dropdown-toggle').dropdown("toggle");
                }
            } else {
                alert("An error occurred while saving")
            }
        });
    }
}
