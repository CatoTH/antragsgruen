CKEDITOR.editorConfig = function (config) {
	config.language = 'de';

	config.toolbar_Full =
		[
			{ name: 'document', items : [ 'Source', '-', 'DocProps', 'Preview', '-', 'Templates' ] },
			{ name: 'clipboard', items : [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
			{ name: 'editing', items : [ 'Find', 'Replace', '-', 'SelectAll', '-', 'SpellChecker', 'Scayt' ] },
			{ name: 'insert', items : [ 'Image', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe' ] },
			'/',
			{ name: 'basicstyles', items : [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
			{ name: 'custom', items : [ 'Abbr', 'Italic' ] },
			{ name: 'paragraph', items : [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv',
				'-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
			{ name: 'links', items : [ 'Link', 'Unlink', 'Anchor' ] },

			'/',
			{ name: 'styles', items : [ 'Format', 'Font', 'FontSize' ] },
			{ name: 'colors', items : [ 'TextColor', 'BGColor' ] },
			{ name: 'tools', items : [ 'Maximize', 'ShowBlocks' ] },
			{ name: 'forms', items : [ 'Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton',
				'HiddenField' ] }
		];
};
