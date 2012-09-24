CKEDITOR.editorConfig = function (config) {
	config.language = 'de';
	config.extraPlugins = 'antraege_bbcode';
	config.toolbar_Animexx = [
		['Source', '-', 'Preview', 'Maximize', 'ShowBlocks'],
		['Cut', 'Copy', 'Paste', 'PasteFromWord'],
		['Undo', 'Redo'],
		['SpecialChar'],
		['Link', 'Unlink'],
//		'/',
		['Bold', 'Italic', 'Underline', 'Strike'],
		['NumberedList', 'BulletedList'],
		['Blockquote']
	];
};
