export class ResponsibilitySetter {
    constructor(private $list: JQuery) {
        $list.find(".respHolder .respUser").on("click", this.userSelected.bind(this));
        $list.find(".respHolder .respCommentRow button").on("click", this.saveComment.bind(this));
    }

    private userSelected(ev: Event) {
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

    private saveComment(ev: Event) {
        ev.preventDefault();
        const $row = $(ev.currentTarget).parents(".respHolder").first();

        const comment = $row.find(".respCommentRow input[type=text]").val() as string;
        const url = $row.data("save-url");

        $.post(url, {
            '_csrf': $('input[name=_csrf]').val(),
            'comment': comment
        }, (ret) => {
            if (ret['success']) {
                $row.find(".responsibilityComment").text(comment);
            } else {
                alert("An error occurred while saving")
            }
        });
    }
}
