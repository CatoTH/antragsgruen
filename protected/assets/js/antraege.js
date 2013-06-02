/*global $*/
/*global console*/

(function () {
	"use strict";

	$(".unterstuetzerInnenwidget .unterstuetzerInnenwidget_add_caller a").click(function (ev) {
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

	$(document).on("change", ".person_selector",function () {
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
		autoGrow_bottomSpace: 20,
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


function instanz_neu_anlegen_init() {
	var $steps = $("#AnlegenWizard").find("li"),
		$step2 = $("#step2"),
		$step3 = $("#step3");
	$step2.hide();
	$step3.hide();
	$("#weiter-1").click(function(ev) {
		ev.preventDefault();
		$("#step1").hide();
		$step2.show();
		$steps.eq(0).removeClass("active");
		$steps.eq(1).addClass("active");
	});
	$("#weiter-2").click(function(ev) {
		ev.preventDefault();
		if ($step2.find(".name input").val() == "") {
			$step2.find(".name .alert").show();
			$step2.find(".name input").focus();
			return;
		}
		if ($step2.find(".url input").val() == "") {
			$step2.find(".url .alert").show();
			$step2.find(".url input").focus();
			return;
		}
		$step2.hide();
		$step3.show();
		$steps.eq(1).removeClass("active");
		$steps.eq(2).addClass("active");
	});
	$step3.find("button[type=submit]").click(function(ev) {

	});
}




/*
 * jQuery scrollintoview() plugin and :scrollable selector filter
 *
 * Version 1.8 (14 Jul 2011)
 * Requires jQuery 1.4 or newer
 *
 * Copyright (c) 2011 Robert Koritnik
 * Licensed under the terms of the MIT license
 * http://www.opensource.org/licenses/mit-license.php
 */
(function(f){var c={vertical:{x:false,y:true},horizontal:{x:true,y:false},both:{x:true,y:true},x:{x:true,y:false},y:{x:false,y:true}};var b={duration:"fast",direction:"both"};var e=/^(?:html)$/i;var g=function(k,j){j=j||(document.defaultView&&document.defaultView.getComputedStyle?document.defaultView.getComputedStyle(k,null):k.currentStyle);var i=document.defaultView&&document.defaultView.getComputedStyle?true:false;var h={top:(parseFloat(i?j.borderTopWidth:f.css(k,"borderTopWidth"))||0),left:(parseFloat(i?j.borderLeftWidth:f.css(k,"borderLeftWidth"))||0),bottom:(parseFloat(i?j.borderBottomWidth:f.css(k,"borderBottomWidth"))||0),right:(parseFloat(i?j.borderRightWidth:f.css(k,"borderRightWidth"))||0)};return{top:h.top,left:h.left,bottom:h.bottom,right:h.right,vertical:h.top+h.bottom,horizontal:h.left+h.right}};var d=function(h){var j=f(window);var i=e.test(h[0].nodeName);return{border:i?{top:0,left:0,bottom:0,right:0}:g(h[0]),scroll:{top:(i?j:h).scrollTop(),left:(i?j:h).scrollLeft()},scrollbar:{right:i?0:h.innerWidth()-h[0].clientWidth,bottom:i?0:h.innerHeight()-h[0].clientHeight},rect:(function(){var k=h[0].getBoundingClientRect();return{top:i?0:k.top,left:i?0:k.left,bottom:i?h[0].clientHeight:k.bottom,right:i?h[0].clientWidth:k.right}})()}};f.fn.extend({scrollintoview:function(j){j=f.extend({},b,j);j.direction=c[typeof(j.direction)==="string"&&j.direction.toLowerCase()]||c.both;var n="";if(j.direction.x===true){n="horizontal"}if(j.direction.y===true){n=n?"both":"vertical"}var l=this.eq(0);var i=l.closest(":scrollable("+n+")");if(i.length>0){i=i.eq(0);var m={e:d(l),s:d(i)};var h={top:m.e.rect.top-(m.s.rect.top+m.s.border.top),bottom:m.s.rect.bottom-m.s.border.bottom-m.s.scrollbar.bottom-m.e.rect.bottom,left:m.e.rect.left-(m.s.rect.left+m.s.border.left),right:m.s.rect.right-m.s.border.right-m.s.scrollbar.right-m.e.rect.right};var k={};if(j.direction.y===true){if(h.top<0){k.scrollTop=m.s.scroll.top+h.top}else{if(h.top>0&&h.bottom<0){k.scrollTop=m.s.scroll.top+Math.min(h.top,-h.bottom)}}}if(j.direction.x===true){if(h.left<0){k.scrollLeft=m.s.scroll.left+h.left}else{if(h.left>0&&h.right<0){k.scrollLeft=m.s.scroll.left+Math.min(h.left,-h.right)}}}if(!f.isEmptyObject(k)){if(e.test(i[0].nodeName)){i=f("html,body")}i.animate(k,j.duration).eq(0).queue(function(o){f.isFunction(j.complete)&&j.complete.call(i[0]);o()})}else{f.isFunction(j.complete)&&j.complete.call(i[0])}}return this}});var a={auto:true,scroll:true,visible:false,hidden:false};f.extend(f.expr[":"],{scrollable:function(k,i,n,h){var m=c[typeof(n[3])==="string"&&n[3].toLowerCase()]||c.both;var l=(document.defaultView&&document.defaultView.getComputedStyle?document.defaultView.getComputedStyle(k,null):k.currentStyle);var o={x:a[l.overflowX.toLowerCase()]||false,y:a[l.overflowY.toLowerCase()]||false,isRoot:e.test(k.nodeName)};if(!o.x&&!o.y&&!o.isRoot){return false}var j={height:{scroll:k.scrollHeight,client:k.clientHeight},width:{scroll:k.scrollWidth,client:k.clientWidth},scrollableX:function(){return(o.x||o.isRoot)&&this.width.scroll>this.width.client},scrollableY:function(){return(o.y||o.isRoot)&&this.height.scroll>this.height.client}};return m.y&&j.scrollableY()||m.x&&j.scrollableX()}})})(jQuery);