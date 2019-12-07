class MotionInitiatorShow {
    constructor() {
        $(".motionData .contactShow").on("click", function (ev) {
            ev.preventDefault();
            $(this).addClass("hidden");
            $(".motionData .contactDetails").removeClass("hidden");
        });
    }
}
