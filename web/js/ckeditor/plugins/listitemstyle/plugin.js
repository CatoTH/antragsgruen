/**
 * @license Copyright (c) 2003-2019, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

( function() {
	CKEDITOR.plugins.listitemstyle = {
		requires: 'dialog,contextmenu',
		lang: 'de,en',
		init: function( editor ) {
			if ( editor.blockless )
				return;

			var def, cmd;

			def = new CKEDITOR.dialogCommand( 'numberedListStyle', {
				requiredContent: 'ol',
				allowedContent: 'ol{list-style-type}[start]; li{list-style-type}[value]',
				contentTransformations: [
					[ 'ol: listTypeToStyle' ]
				]
			} );
			cmd = editor.addCommand( 'numberedListStyle', def );
			editor.addFeature( cmd );
			CKEDITOR.dialog.add( 'numberedListStyle', this.path + 'dialogs/liststyle.js' );

			def = new CKEDITOR.dialogCommand( 'numberedListItemStyle', {
				requiredContent: 'li',
				allowedContent: 'li[value]',
				contentTransformations: [
					[ 'li: listTypeToStyle' ]
				]
			} );
			cmd = editor.addCommand( 'numberedListItemStyle', def );
			editor.addFeature( cmd );
			CKEDITOR.dialog.add( 'numberedListItemStyle', this.path + 'dialogs/listitemstyle.js' );

			//Register map group;
			editor.addMenuGroup( 'list', 108 );

			editor.addMenuItems( {
				numberedlist: {
					label: editor.lang.liststyle.numberedTitle,
					group: 'list',
					command: 'numberedListStyle'
				},
                numberedlistItem: {
					label: editor.lang.listitemstyle.numberedItemTitle,
					group: 'list',
					command: 'numberedListItemStyle'
				}
			} );

			editor.contextMenu.addListener( function( element ) {
				if ( !element || element.isReadOnly() )
					return null;

				while ( element ) {
				    var name = element.getName();
					if ( name == 'ol' )
						return { numberedlist: CKEDITOR.TRISTATE_OFF };
					else if ( name == 'li' && element.getParent().getName() == 'ol' ) {
						return { numberedlist: CKEDITOR.TRISTATE_OFF, numberedlistItem: CKEDITOR.TRISTATE_OFF };
					}

					element = element.getParent();
				}
				return null;
			} );
		}
	};

	CKEDITOR.plugins.add( 'listitemstyle', CKEDITOR.plugins.listitemstyle );
} )();
