CKEDITOR.plugins.add('abbr', {
	init:function (editor) {
		editor.addCommand('abbrDialog', new CKEDITOR.dialogCommand('abbrDialog'));
		editor.ui.addButton('Abbr',
			{
				label:'Abkürzung einfügen',
				command:'abbrDialog',
				icon:'/js/ckeditor.abbr/images/icon.png'
			});
		CKEDITOR.dialog.add('abbrDialog', function (editor) {
			return {
				title:'Abkürzung',
				minWidth:400,
				minHeight:200,
				contents:[
					{
						id:'tab1',
						label:'Einstellungen',
						elements:[
							{
								type:'text',
								id:'abbr',
								label:'Abkürzung',
								validate:CKEDITOR.dialog.validate.notEmpty("Es muss eine Abkürzung angegeben werden")
							},
							{
								type:'text',
								id:'title',
								label:'Expansion / Tooltip-Text',
								validate:CKEDITOR.dialog.validate.notEmpty("Es muss eine Expansion angegeben werden")
							}
						]
					}
				],
				onOk:function () {
					var dialog = this;
					var abbr = editor.document.createElement('abbr');

					abbr.setAttribute('title', dialog.getValueOf('tab1', 'title'));
					abbr.setText(dialog.getValueOf('tab1', 'abbr'));

					editor.insertElement(abbr);
				}
			};
		});
	}
});