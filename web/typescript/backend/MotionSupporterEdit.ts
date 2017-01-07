declare let Sortable: any;

export class MotionSupporterEdit {
    constructor(private $supporterHolder: JQuery) {
        let $sortable = this.$supporterHolder.find("> ul");
        if ($sortable.length > 0) {
            Sortable.create($sortable[0], {draggable: 'li'});
        }

        $(".supporterRowAdder").click(function (ev) {
            $sortable.append($(this).data("content"));
            ev.preventDefault();
        });
        $sortable.on("click", ".delSupporter", function (ev) {
            ev.preventDefault();
            $(this).parents("li").first().remove();
        });

        let $fullTextHolder = $("#fullTextHolder");
        $supporterHolder.find(".fullTextAdd").click(function () {
            let lines = $fullTextHolder.find('textarea').val().split(";"),
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
                if ($newEl.find('input.supporterOrga').length > 0) {
                    let parts = lines[i].split(',');
                    $newEl.find('input.supporterName').val(parts[0].trim());
                    if (parts.length > 1) {
                        $newEl.find('input.supporterOrga').val(parts[1].trim());
                    }
                } else {
                    $newEl.find('input.supporterName').val(lines[i]);
                }
            }
            $fullTextHolder.find('textarea').select().focus();
            $firstAffectedRow.scrollintoview();
        });
    }
}
