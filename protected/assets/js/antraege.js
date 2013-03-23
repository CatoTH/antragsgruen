/*global $*/
/*global console*/

(function () {
	"use strict";

	$(".unterstuetzerwidget .unterstuetzerwidget_add_caller a").click(function (ev) {
		var text = $(this).parents(".unterstuetzerwidget").data("neutemplate");
		$(text).insertBefore($(this).parents(".unterstuetzerwidget_add_caller"));
		//$(this).parents(".unterstuetzerwidget_add_caller").insertBefore(text);
		ev.preventDefault();
	});
	$(".unterstuetzerwidget_adder").hide();
	if ($(".unterstuetzerwidget").length > 0) $(function() {
		$(".unterstuetzerwidget").sortable({
			handle: ".sort_handle",
			tolerance: "pointer",
			containment: "parent",
			axis: "y"
		});
	});

	$(document).change(".person_selector",function () {
		var $t = $(this);

		if ($t.val() === "neu") {
			$t.parents(".unterstuetzerwidget_adder").find(".unterstuetzer_neu_holder").show();
		} else {
			$t.parents(".unterstuetzerwidget_adder").find(".unterstuetzer_neu_holder").hide();
		}
	}).trigger("change");

	$(".antragabsatz_holder .kommentare .hider").click(function (ev) {
		$(this).hide();
		$(this).parents(".kommentare").find(".shower").css("display", "block");
		$(this).parents(".kommentare").find(".text").show();
		ev.preventDefault();
	});

	$(".antragabsatz_holder .kommentare .shower").click(function (ev) {
		$(this).hide();
		$(this).parents(".kommentare").find(".hider").css("display", "block");
		$(this).parents(".kommentare").find(".text").show();
		ev.preventDefault();
	});

	$(".kommentare .shower").click(function (ev) {
		ev.preventDefault();
		$(this).parents(".row-absatz").find(".kommentarform").show();
		$(this).hide();
		$(this).parents(".row-absatz").find(".kommentare .hider").show();
	});

	$(".kommentare .hider").click(function (ev) {
		ev.preventDefault();
		$(this).parents(".row-absatz").find(".kommentarform").hide();
		$(this).hide();
		$(this).parents(".row-absatz").find(".kommentare .shower").css("display", "block");
	});

	$(".kommentare_closed_absatz .kommentare .hider").click();

	$(".kommentarform .del_link a").click(function (ev) {
		if (!confirm("Diesen Kommentar wirklich löschen?")) {
			ev.preventDefault();
		}
	});

	$(".antrags_text_holder .aenders .aender_link").mouseover(function () {
		var ae = $(this).data("id"),
			$par = $(this).parents(".row-absatz");
		$par.find(".ae_" + ae).show();
		$par.find(".orig").hide();
	}).mouseout(function () {
		var ae = $(this).data("id"),
			$par = $(this).parents(".row-absatz");
		$par.find(".ae_" + ae).hide();
		$par.find(".orig").show();
	});

	$(".js_protection_hint").remove();
	$("input[name=form_token]").each(function () {
		$(this).parents("form").append("<input name='" + $(this).val() + "' value='1' type='hidden'>");
		$(this).remove();
	});

	$(".kommentarform").submit(function(ev) {
		$(this).find(".row").each(function() {
			var $row = $(this);
			if ($row.find("label.required").length > 0 && $row.find("input, textarea").val() == "") {
				ev.preventDefault();
				alert("Bitte fülle alle Felder aus");
				$row.find("input, textarea").focus();
			}
		});

	});

}());
