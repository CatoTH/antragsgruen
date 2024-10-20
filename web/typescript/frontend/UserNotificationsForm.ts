export class UserNotificationsForm {
    constructor(private $widget: JQuery) {
        $(".notiComment input").on("change", (ev) => {
            if ($(ev.currentTarget).prop("checked")) {
                $(".commentSettings").removeClass("hidden");
            } else {
                $(".commentSettings").addClass("hidden");
            }
        }).trigger("change");

        $(".notiAmendment input").on("change", (ev) => {
            if ($(ev.currentTarget).prop("checked")) {
                $(".amendmentSettings").removeClass("hidden");
            } else {
                $(".amendmentSettings").addClass("hidden");
            }
        }).trigger("change");
    }
}
