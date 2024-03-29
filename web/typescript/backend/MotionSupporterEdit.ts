declare let Sortable: any;
declare let ClipboardJS: any;

export class MotionSupporterEdit {
    constructor(private $supporterHolder: JQuery) {
        let $sortable = this.$supporterHolder.find("> ul");
        if ($sortable.length > 0) {
            Sortable.create($sortable[0], {draggable: 'li'});
        }

        $(".supporterRowAdder").on("click", function (ev) {
            $sortable.append($(this).data("content"));
            ev.preventDefault();
        });
        $sortable.on("click", ".delSupporter", function (ev) {
            ev.preventDefault();
            $(this).parents("li").first().remove();
        });

        let $fullTextHolder = $("#supporterFullTextHolder");
        $supporterHolder.find(".fullTextAdd").on("click", () => {
            let lines = ($fullTextHolder.find('textarea').val() as string).split(";"),
                template = $(".supporterRowAdder").data("content"),
                getNewElement = function () {
                    let $rows = $sortable.find("> li");
                    for (let i = 0; i < $rows.length; i++) {
                        let $row = $rows.eq(i);
                        if ($row.find(".supporterName").val() == '' && $row.find(".supporterOrga").val() == '') return $row;
                    }
                    // No empty row found
                    let $newEl = $(template);
                    $sortable.append($newEl);
                    return $newEl;
                };
            let $firstAffectedRow = null;
            for (let i = 0; i < lines.length; i++) {
                if (lines[i] == '') {
                    continue;
                }
                let $newEl = getNewElement();
                if ($firstAffectedRow == null) $firstAffectedRow = $newEl;
                let parts = lines[i].split(',');

                let name = parts.shift();
                $newEl.find('input.supporterName').val(name.trim());

                if ($newEl.find('input.supporterOrga').length > 0 && parts.length > 0) {
                    let orga = parts.shift();
                    $newEl.find('input.supporterOrga').val(orga.trim());
                }
                if ($newEl.find('.colGender').length > 0 && parts.length > 0) {
                    $newEl.find('.colGender').find('select').val(parts[0]);
                }
            }
            $fullTextHolder.find('textarea').trigger('select').trigger('focus');
            $firstAffectedRow.scrollintoview();
        });

        const $copier = $supporterHolder.find(".fullTextCopy");
        const clipboard = new ClipboardJS($copier[0], {
            text: function () {
                let supporters = [];
                $supporterHolder.find('.supporterRow').each((i, el) => {
                    let $el = $(el),
                        parts = [];
                    if ($el.find(".supporterName").length) {
                        parts.push(($el.find(".supporterName").val() as string).replace(/,/, ' ').replace(/;/, ' '));
                    }
                    if ($el.find(".supporterOrga").length) {
                        parts.push(($el.find(".supporterOrga").val() as string).replace(/,/, ' ').replace(/;/, ' '));
                    }
                    if ($el.find(".colGender").length) {
                        parts.push($el.find(".colGender select").val());
                    }
                    supporters.push(parts.join(','));
                });
                return supporters.join(";");
            }
        });
        clipboard.on('success', () => {
            $copier.addClass("done");
            window.setTimeout(() => {
                $copier.removeClass("done");
            }, 1000);
        });

        clipboard.on('error', () => {
            alert("Could not copy the URL to the clipboard");
        });

        $('[data-toggle="tooltip"]').tooltip();
    }
}
