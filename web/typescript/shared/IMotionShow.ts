class IMotionShow {
    public initContactShow()
    {
        $(".motionData .contactShow").on("click", function (ev) {
            ev.preventDefault();
            $(this).addClass("hidden");
            $(".motionData .contactDetails").removeClass("hidden");
        });
    }

    public initExpandableList()
    {
        document.querySelectorAll('.expandableList').forEach((el: HTMLDivElement) => {
            el.querySelector('.btnShowAll').addEventListener('click', () => {
                el.querySelector('.shortList').classList.add('hidden');
                el.querySelector('.fullList').classList.remove('hidden');
                el.querySelector('.btnShowAll').classList.add('hidden');
            });
        });
    }

    public initAmendmentTextMode()
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

    public initDelSubmit() {
        $("form.delLink").on("submit", (ev) => {
            ev.preventDefault();
            let form: HTMLFormElement = ev.target as HTMLFormElement;
            bootbox.confirm(__t("std", "del_confirm"), function (result) {
                if (result) {
                    // noinspection JSDeprecatedSymbols
                    form.submit(); // Native submit() function, not the jQuery one
                }
            });
        });
    }

    public initCmdEnterSubmit() {
        $(document).on('keypress', 'form textarea', (ev) => {
            if (ev.originalEvent['metaKey'] && ev.originalEvent['keyCode'] === 13) {
                let $textarea = $(ev.currentTarget);
                $textarea.parents("form").first().find("button[type=submit]").trigger("click");
            }
        });
    }

    public initDataTableActions() {
        document.querySelectorAll('.tagAdderHolder').forEach(el => {
            el.addEventListener('click', (ev) => {
                ev.preventDefault();
                el.classList.add('hidden');
                document.getElementById('tagAdderForm').classList.remove('hidden');
            });
        });

        document.querySelectorAll('.motionData .btnHistoryOpener').forEach(el => {
           el.addEventListener('click', () => {
               document.querySelector('.motionData .historyOpener').classList.add('hidden');
               document.querySelector('.motionData .fullHistory').classList.remove('hidden');
           });
        });
    }
}
