/*global $*/
/*global console*/

(function () {
	"use strict";

	$(".unterstuetzerInnennwidget .unterstuetzerInnenwidget_add_caller a").click(function (ev) {
		var text = $(this).parents(".unterstuetzerInnenwidget").data("neutemplate");
		$(text).insertBefore($(this).parents(".unterstuetzerInnenwidget_add_caller"));
		//$(this).parents(".unterstuetzerInnenwidget_add_caller").insertBefore(text);
		ev.preventDefault();
	});
	$(".unterstuetzerInnenwidget_adder").hide();
	if ($(".unterstuetzerInnenwidget").length > 0) $(function() {
		$(".unterstuetzerInnenwidget").sortable({
			handle: ".sort_handle",
			tolerance: "pointer",
			containment: "parent",
			axis: "y"
		});
	});

	$(document).change(".person_selector",function () {
		var $t = $(this);

		if ($t.val() === "neu") {
			$t.parents(".unterstuetzerInnenwidget_adder").find(".unterstuetzerIn_neu_holder").show();
		} else {
			$t.parents(".unterstuetzerInnenwidget_adder").find(".unterstuetzerIn_neu_holder").hide();
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


function ckeditor_bbcode(id) {

	CKEDITOR.replace(id, {
		allowedContent: 'b s i u p blockquote ul ol li a[href];',
		// Remove unused plugins.
		//removePlugins: 'bidi,dialogadvtab,div,filebrowser,flash,format,forms,horizontalrule,iframe,justify,liststyle,pagebreak,showborders,stylescombo,table,tabletools,templates',
		removePlugins: 'stylescombo,format,save,newpage,print,templates,showblocks,specialchar,about,preview,pastetext,pastefromword,magicline' + ',sourcearea',
		extraPlugins: 'autogrow,wordcount,bbcode',
		scayt_sLang: 'de_DE',
		// Width and height are not supported in the BBCode format, so object resizing is disabled.
		disableObjectResizing: true,
		// Whether or not you want to show the Word Count
		showWordCount: true,
		// Whether or not you want to show the Char Count
		showCharCount: true,
		toolbar:
			[
				{ name: 'document',    items : [ 'Source','-','Save','NewPage','DocProps','Preview','Print','-','Templates' ] },
				{ name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
				//{ name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
				{ name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
				{ name: 'links',       items : [ 'Link','Unlink','Anchor' ] },
				{ name: 'clipboard',   items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
				{ name: 'editing',     items : [ 'Find','Replace','-','SelectAll','-','SpellChecker', 'Scayt' ] },
				{ name: 'forms',       items : [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField' ] },
				{ name: 'insert',      items : [ 'Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak' ] },
				{ name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] },
				{ name: 'colors',      items : [ 'TextColor','BGColor' ] },
				{ name: 'tools',       items : [ 'Maximize', 'ShowBlocks','-','About' ] }
			]

	});

}

