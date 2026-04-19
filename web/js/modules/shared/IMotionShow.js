// @ts-check

export class IMotionShow {
    initContactShow() {
        $(".motionData .contactShow").on("click", function (ev) {
            ev.preventDefault();
            $(this).addClass("hidden");
            $(".motionData .contactDetails").removeClass("hidden");
        });
    }

    initAgreeToProposal() {
        $(".agreeToProposal .btnUpdateDecision").on("click", (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            $(".agreeToProposal").removeClass("notUpdating").addClass("updating");
        });
    }

    initExpandableList() {
        document.querySelectorAll('.expandableList').forEach((el) => {
            /** @type {HTMLElement} */
            const container = el;
            container.querySelector('.btnShowAll').addEventListener('click', () => {
                container.querySelector('.shortList').classList.add('hidden');
                container.querySelector('.fullList').classList.remove('hidden');
                container.querySelector('.btnShowAll').classList.add('hidden');
            });
        });
    }

    initAmendmentTextMode() {
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

    initDelSubmit() {
        $("form.delLink").on("submit", (ev) => {
            ev.preventDefault();
            /** @type {HTMLFormElement} */
            const form = ev.target;
            bootbox.confirm(__t("std", "del_confirm"), function (result) {
                if (result) {
                    // noinspection JSDeprecatedSymbols
                    form.submit(); // Native submit() function, not the jQuery one
                }
            });
        });
    }

    initCmdEnterSubmit() {
        $(document).on('keypress', 'form textarea', (ev) => {
            if (ev.originalEvent['metaKey'] && ev.originalEvent.key === 'Enter') {
                const $textarea = $(ev.currentTarget);
                $textarea.parents("form").first().find("button[type=submit]").trigger("click");
            }
        });
    }

    initDataTableActions() {
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
